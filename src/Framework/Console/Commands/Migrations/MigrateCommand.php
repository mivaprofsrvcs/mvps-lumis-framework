<?php

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Events\SchemaLoaded;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Database\SqlServerConnection;
use MVPS\Lumis\Framework\Contracts\Console\Isolatable;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use PDOException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'migrate')]
class MigrateCommand extends BaseCommand implements Isolatable
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Run the database migrations';

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected Dispatcher $dispatcher;

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected Migrator $migrator;

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'migrate {--database= : The database connection to use}
		{--force : Force the operation to run when in production}
		{--path=* : The path(s) to the migrations files to be executed}
		{--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
		{--schema-path= : The path to a schema dump file}
		{--pretend : Dump the SQL queries that would be run}
		{--seed : Indicates if the seed task should be re-run}
		{--seeder= : The class name of the root seeder}
		{--step : Force the migrations to be run so they can be rolled back individually}
		{--graceful : Return a successful exit code even if an error occurs}';

	/**
	 * Create a new migrate command instance.
	 */
	public function __construct(Migrator $migrator, Dispatcher $dispatcher)
	{
		parent::__construct();

		$this->migrator = $migrator;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Create a missing MySQL database.
	 *
	 * @throws \RuntimeException
	 */
	protected function createMissingMysqlDatabase(ConnectionInterface|Connection $connection): bool
	{
		$connectionDatabaseName = "database.connections.{$connection->getName()}.database";

		if ($this->lumis['config']->get($connectionDatabaseName) !== $connection->getDatabaseName()) {
			return false;
		}

		if (! $this->option('force') && $this->option('no-interaction')) {
			return false;
		}

		if (! $this->option('force') && ! $this->option('no-interaction')) {
			$this->components->warn(sprintf(
				"The database '%s' does not exist on the '%s' connection.",
				$connection->getDatabaseName(),
				$connection->getName()
			));

			if (! confirm('Would you like to create it?', default: true)) {
				$this->components->info('Operation cancelled. No database was created.');

				throw new RuntimeException('Database was not created. Aborting migration.');
			}
		}

		try {
			$this->lumis['config']->set($connectionDatabaseName, null);

			$this->lumis['db']->purge();

			$freshConnection = $this->migrator->resolveConnection($this->option('database'));

			return tap(
				$freshConnection->unprepared("CREATE DATABASE IF NOT EXISTS `{$connection->getDatabaseName()}`"),
				fn () => $this->lumis['db']->purge()
			);
		} finally {
			$this->lumis['config']->set($connectionDatabaseName, $connection->getDatabaseName());
		}

		return true;
	}

	/**
	 * Create a missing SQLite database.
	 *
	 * @throws \RuntimeException
	 */
	protected function createMissingSqliteDatabase(string $path): bool
	{
		if ($this->option('force')) {
			return touch($path);
		}

		if ($this->option('no-interaction')) {
			return false;
		}

		$this->components->warn('The SQLite database configured for this application does not exist: ' . $path);

		if (! confirm('Would you like to create it?', default: true)) {
			$this->components->info('Operation cancelled. No database was created.');

			throw new RuntimeException('Database was not created. Aborting migration.');
		}

		return touch($path);
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		if (! $this->confirmToProceed()) {
			return BaseCommand::FAILURE;
		}

		try {
			$this->runMigrations();
		} catch (Throwable $e) {
			if ($this->option('graceful')) {
				$this->components->warn($e->getMessage());

				return BaseCommand::SUCCESS;
			}

			throw $e;
		}

		return BaseCommand::SUCCESS;
	}

	/**
	 * Load the schema state to seed the initial database schema structure.
	 */
	protected function loadSchemaState(): void
	{
		$connection = $this->migrator->resolveConnection($this->option('database'));
		$path = $this->schemaPath($connection);

		// Checks if the connection supports schema loading and the schema file
		// exists. If either condition is not met, skips schema loading and
		// proceeds with standard migration operations.
		if ($connection instanceof SqlServerConnection || ! is_file($path)) {
			return;
		}

		$this->components->info('Loading stored database schemas.');

		$this->components->task($path, function () use ($connection, $path) {
			// The schema file will recreate the "migrations" table. To prevent
			// conflicts, delete it beforehand to ensure a clean state.
			$this->migrator->deleteRepository();

			$connection->getSchemaState()
				->handleOutputUsing(fn ($type, $buffer) => $this->output->write($buffer))
				->load($path);
		});

		$this->newLine();

		// Dispatch an event to notify listeners that the schema has been loaded.
		// This allows developers to perform post-load tasks, such as seeding
		// database tables.
		$this->dispatcher->dispatch(new SchemaLoaded($connection, $path));
	}

	/**
	 * Prepare the migration database for running.
	 */
	protected function prepareDatabase(): void
	{
		if (! $this->repositoryExists()) {
			$this->components->info('Preparing database.');

			$this->components->task('Creating migration table', function () {
				return (int) $this->callSilent('migrate:install', array_filter([
					'--database' => $this->option('database'),
				])) === 0;
			});

			$this->newLine();
		}

		if (! $this->migrator->hasRunAnyMigrations() && ! $this->option('pretend')) {
			$this->loadSchemaState();
		}
	}

	/**
	 * Determine if the migrator repository exists.
	 */
	protected function repositoryExists(): bool
	{
		return (bool) retry(2, fn () => $this->migrator->repositoryExists(), 0, function ($e) {
			try {
				if ($e->getPrevious() instanceof SQLiteDatabaseDoesNotExistException) {
					return $this->createMissingSqliteDatabase($e->getPrevious()->path);
				}

				$connection = $this->migrator->resolveConnection($this->option('database'));

				if (
					$e->getPrevious() instanceof PDOException &&
					$e->getPrevious()->getCode() === 1049 &&
					in_array($connection->getDriverName(), ['mysql', 'mariadb'])
				) {
					return $this->createMissingMysqlDatabase($connection);
				}

				return false;
			} catch (Throwable) {
				return false;
			}
		});
	}

	/**
	 * Run the pending migrations.
	 */
	protected function runMigrations(): void
	{
		$this->migrator->usingConnection($this->option('database'), function () {
			$this->prepareDatabase();

			// Next, check if a path option has been defined. If so, use the path
			// relative to the root of this installation folder to allow running
			// migrations for any path within the application.
			$this->migrator->setOutput($this->output)
				->run($this->getMigrationPaths(), [
					'pretend' => $this->option('pretend'),
					'step' => $this->option('step'),
				]);

			// If the "seed" option is provided, re-run the database seed task
			// to populate  the database. This is useful when adding a migration
			// and seed simultaneously,  as this command handles both steps.
			if ($this->option('seed') && ! $this->option('pretend')) {
				$this->call('db:seed', [
					'--class' => $this->option('seeder') ?: 'Database\\Seeders\\DatabaseSeeder',
					'--force' => true,
				]);
			}
		});
	}

	/**
	 * Get the path to the stored schema for the given connection.
	 */
	protected function schemaPath(ConnectionInterface|Connection $connection): string
	{
		if ($this->option('schema-path')) {
			return $this->option('schema-path');
		}

		$path = database_path('schema/' . $connection->getName() . '-schema.dump');

		if (file_exists($path)) {
			return $path;
		}

		return database_path('schema/' . $connection->getName() . '-schema.sql');
	}
}
