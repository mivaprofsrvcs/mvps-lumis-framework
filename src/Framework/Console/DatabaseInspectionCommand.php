<?php

namespace MVPS\Lumis\Framework\Console;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use MVPS\Lumis\Framework\Support\Arr;

abstract class DatabaseInspectionCommand extends Command
{
	/**
	 * Get the connection configuration details for the given connection.
	 */
	protected function getConfigFromDatabase(string|null $database = null): array
	{
		$database ??= config('database.default');

		return Arr::except(config('database.connections.' . $database), ['password']);
	}

	/**
	 * Get the number of open connections for a database.
	 */
	protected function getConnectionCount(ConnectionInterface $connection): int|null
	{
		$result = match (true) {
			$connection instanceof MySqlConnection =>$connection->selectOne(
				'show status where variable_name = "threads_connected"'
			),
			$connection instanceof PostgresConnection => $connection->selectOne(
				'select count(*) as "Value" from pg_stat_activity'
			),
			$connection instanceof SqlServerConnection => $connection->selectOne(
				'select count(*) Value from sys.dm_exec_sessions where status = ?',
				['running']
			),
			default => null,
		};

		if (! $result) {
			return null;
		}

		return Arr::wrap((array) $result)['Value'];
	}

	/**
	 * Get a human-readable name for the given connection.
	 */
	protected function getConnectionName(ConnectionInterface $connection, string $database): string
	{
		return match (true) {
			$connection instanceof MySqlConnection && $connection->isMaria() => 'MariaDB',
			$connection instanceof MySqlConnection => 'MySQL',
			$connection instanceof MariaDbConnection => 'MariaDB',
			$connection instanceof PostgresConnection => 'PostgreSQL',
			$connection instanceof SQLiteConnection => 'SQLite',
			$connection instanceof SqlServerConnection => 'SQL Server',
			default => $database,
		};
	}

	/**
	 * Remove the table prefix from a table name, if it exists.
	 */
	protected function withoutTablePrefix(ConnectionInterface $connection, string $table): string
	{
		$prefix = $connection->getTablePrefix();

		return str_starts_with($table, $prefix)
			? substr($table, strlen($prefix))
			: $table;
	}
}
