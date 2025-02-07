<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

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
