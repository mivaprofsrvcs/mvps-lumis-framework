<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use Illuminate\Support\ConfigurationUrlParser;
use MVPS\Lumis\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

#[AsCommand(name: 'db')]
class DbCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Start a new database CLI session';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'db
		{connection? : The database connection that should be used}
		{--read : Connect to the read connection}
		{--write : Connect to the write connection}';

	/**
	 * Get the arguments for the database client command.
	 */
	public function commandArguments(array $connection): array
	{
		$driver = ucfirst($connection['driver']);

		return $this->{"get{$driver}Arguments"}($connection);
	}

	/**
	 * Get the environment variables for the database client command.
	 */
	public function commandEnvironment(array $connection): array|null
	{
		$driver = ucfirst($connection['driver']);

		if (method_exists($this, "get{$driver}Environment")) {
			return $this->{"get{$driver}Environment"}($connection);
		}

		return null;
	}

	/**
	 * Get the database client command to run.
	 */
	public function getCommand(array $connection): string
	{
		return [
			'mysql' => 'mysql',
			'mariadb' => 'mysql',
			'pgsql' => 'psql',
			'sqlite' => 'sqlite3',
			'sqlsrv' => 'sqlcmd',
		][$connection['driver']];
	}

	/**
	 * Get the database connection configuration.
	 *
	 * @throws \UnexpectedValueException
	 */
	public function getConnection(): array
	{
		$db = $this->argument('connection');

		$connection = $this->lumis['config'][
			'database.connections.' . ($db ?? $this->lumis['config']['database.default'])
		];

		if (empty($connection)) {
			throw new UnexpectedValueException("Invalid database connection [{$db}].");
		}

		if (! empty($connection['url'])) {
			$connection = (new ConfigurationUrlParser)->parseConfiguration($connection);
		}

		if ($this->option('read')) {
			if (is_array($connection['read']['host'])) {
				$connection['read']['host'] = $connection['read']['host'][0];
			}

			$connection = array_merge($connection, $connection['read']);
		} elseif ($this->option('write')) {
			if (is_array($connection['write']['host'])) {
				$connection['write']['host'] = $connection['write']['host'][0];
			}

			$connection = array_merge($connection, $connection['write']);
		}

		return $connection;
	}

	/**
	 * Get the arguments for the MariaDB CLI.
	 */
	protected function getMariaDbArguments(array $connection): array
	{
		return $this->getMysqlArguments($connection);
	}

	/**
	 * Get the arguments for the MySQL CLI.
	 */
	protected function getMysqlArguments(array $connection): array
	{
		return array_merge(
			[
				'--host=' . $connection['host'],
				'--port=' . $connection['port'],
				'--user=' . $connection['username'],
			],
			$this->getOptionalArguments(
				[
				'password' => '--password=' . $connection['password'],
				'unix_socket' => '--socket=' . ($connection['unix_socket'] ?? ''),
				'charset' => '--default-character-set=' . ($connection['charset'] ?? ''),
				],
				$connection
			),
			[$connection['database']]
		);
	}

	/**
	 * Get the optional arguments based on the connection configuration.
	 */
	protected function getOptionalArguments(array $args, array $connection): array
	{
		return array_values(array_filter(
			$args,
			fn ($key) => ! empty($connection[$key]),
			ARRAY_FILTER_USE_KEY
		));
	}

	/**
	 * Get the arguments for the Postgres CLI.
	 */
	protected function getPgsqlArguments(array $connection): array
	{
		return [$connection['database']];
	}

	/**
	 * Get the environment variables for the Postgres CLI.
	 */
	protected function getPgsqlEnvironment(array $connection): array|null
	{
		return array_merge(
			...$this->getOptionalArguments(
				[
					'username' => ['PGUSER' => $connection['username']],
					'host' => ['PGHOST' => $connection['host']],
					'port' => ['PGPORT' => $connection['port']],
					'password' => ['PGPASSWORD' => $connection['password']],
				],
				$connection
			)
		);
	}

	/**
	 * Get the arguments for the SQLite CLI.
	 */
	protected function getSqliteArguments(array $connection): array
	{
		return [$connection['database']];
	}

	/**
	 * Get the arguments for the SQL Server CLI.
	 */
	protected function getSqlsrvArguments(array $connection): array
	{
		return array_merge(
			...$this->getOptionalArguments(
				[
					'database' => ['-d', $connection['database']],
					'username' => ['-U', $connection['username']],
					'password' => ['-P', $connection['password']],
					'host' => [
						'-S',
						'tcp:' . $connection['host'] . ($connection['port'] ? ',' . $connection['port'] : ''),
					],
					'trust_server_certificate' => ['-C'],
				],
				$connection
			)
		);
	}

	/**
	 * Execute the db console command.
	 */
	public function handle(): int
	{
		$connection = $this->getConnection();

		if (! isset($connection['host']) && $connection['driver'] !== 'sqlite') {
			$this->components->error('No host specified for this database connection.');

			$this->line(
				'  Use the <options=bold>[--read]</> and <options=bold>[--write]</>' .
				' options to specify a read or write connection.'
			);

			$this->newLine();

			return Command::FAILURE;
		}

		(new Process(
			array_merge([$this->getCommand($connection)], $this->commandArguments($connection)),
			null,
			$this->commandEnvironment($connection)
		))
		->setTimeout(null)
		->setTty(true)
		->mustRun(function ($type, $buffer) {
			$this->output->write($buffer);
		});

		return Command::SUCCESS;
	}
}
