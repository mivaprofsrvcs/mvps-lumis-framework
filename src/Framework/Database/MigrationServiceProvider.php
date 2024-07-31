<?php

namespace MVPS\Lumis\Framework\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use MVPS\Lumis\Framework\Console\Commands\Migrations\FreshCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\InstallCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\MigrateCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\MigrateMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\MigrationCreator;
use MVPS\Lumis\Framework\Console\Commands\Migrations\RefreshCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\ResetCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\RollbackCommand;
use MVPS\Lumis\Framework\Console\Commands\Migrations\StatusCommand;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * The migration commands to be registered.
	 *
	 * @var array
	 */
	protected array $commands = [
		'Migrate' => MigrateCommand::class,
		'MigrateFresh' => FreshCommand::class,
		'MigrateInstall' => InstallCommand::class,
		'MigrateMake' => MigrateMakeCommand::class,
		'MigrateRefresh' => RefreshCommand::class,
		'MigrateReset' => ResetCommand::class,
		'MigrateRollback' => RollbackCommand::class,
		'MigrateStatus' => StatusCommand::class,
	];

	/**
	 * Get the migration services provided by the provider.
	 */
	public function provides(): array
	{
		return array_merge(
			['migrator', 'migration.repository', 'migration.creator'],
			array_values($this->commands)
		);
	}

	/**
	 * Register the migration service provider.
	 */
	public function register(): void
	{
		$this->registerRepository();

		$this->registerMigrator();

		$this->registerCreator();

		$this->registerCommands($this->commands);
	}

	/**
	 * Register the migration commands.
	 */
	protected function registerCommands(array $commands): void
	{
		foreach (array_keys($commands) as $command) {
			$this->{"register{$command}Command"}();
		}

		$this->commands(array_values($commands));
	}

	/**
	 * Register the migration creator.
	 */
	protected function registerCreator(): void
	{
		$this->app->singleton('migration.creator', function ($app) {
			return new MigrationCreator($app['files'], $app->basePath('stubs'));
		});
	}

	/**
	 * Register the migrate command.
	 */
	protected function registerMigrateCommand(): void
	{
		$this->app->singleton(MigrateCommand::class, function ($app) {
			return new MigrateCommand($app['migrator'], $app[Dispatcher::class]);
		});
	}

	/**
	 * Register the migrate fresh command.
	 */
	protected function registerMigrateFreshCommand(): void
	{
		$this->app->singleton(FreshCommand::class, function ($app) {
			return new FreshCommand($app['migrator']);
		});
	}

	/**
	 * Register the migrate install command.
	 */
	protected function registerMigrateInstallCommand(): void
	{
		$this->app->singleton(InstallCommand::class, function ($app) {
			return new InstallCommand($app['migration.repository']);
		});
	}

	/**
	 * Register the command.
	 */
	protected function registerMigrateMakeCommand(): void
	{
		$this->app->singleton(MigrateMakeCommand::class, function ($app) {
			// Register the migration creator, allowing for custom implementations.
			// Then, create the migration command and inject the creator.
			// The creator handles migration file creation.
			$creator = $app['migration.creator'];

			$composer = $app['composer'];

			return new MigrateMakeCommand($creator, $composer);
		});
	}

	/**
	 * Register the migrate refresh command.
	 */
	protected function registerMigrateRefreshCommand(): void
	{
		$this->app->singleton(RefreshCommand::class);
	}

	/**
	 * Register migrate reset command.
	 */
	protected function registerMigrateResetCommand(): void
	{
		$this->app->singleton(ResetCommand::class, function ($app) {
			return new ResetCommand($app['migrator']);
		});
	}

	/**
	 * Register the migrate rollback command.
	 */
	protected function registerMigrateRollbackCommand(): void
	{
		$this->app->singleton(RollbackCommand::class, function ($app) {
			return new RollbackCommand($app['migrator']);
		});
	}

	/**
	 * Register the migrate status command.
	 */
	protected function registerMigrateStatusCommand(): void
	{
		$this->app->singleton(StatusCommand::class, function ($app) {
			return new StatusCommand($app['migrator']);
		});
	}

	/**
	 * Register the migrator service.
	 */
	protected function registerMigrator(): void
	{
		// The migrator is responsible for actually running and rollback the migration
		// files in the application. We'll pass in our database connection resolver
		// so the migrator can resolve any of these connections when it needs to.
		$this->app->singleton('migrator', function ($app) {
			$repository = $app['migration.repository'];

			return new Migrator($repository, $app['db'], $app['files'], $app['events']);
		});
	}

	/**
	 * Register the migration repository service.
	 */
	protected function registerRepository(): void
	{
		$this->app->singleton('migration.repository', function ($app) {
			$migrations = $app['config']['database.migrations'];

			$table = is_array($migrations) ? ($migrations['table'] ?? null) : $migrations;

			return new DatabaseMigrationRepository($app['db'], $table);
		});
	}
}
