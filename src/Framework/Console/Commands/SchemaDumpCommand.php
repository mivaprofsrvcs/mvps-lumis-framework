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

namespace MVPS\Lumis\Framework\Console\Commands;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\SchemaDumped;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schema:dump')]
class SchemaDumpCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Dump the given database schema';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'schema:dump
		{--database= : The database connection to use}
		{--path= : The path where the schema dump file should be stored}
		{--prune : Delete all existing migration files}';

	/**
	 * Execute the schema dump command.
	 */
	public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher): void
	{
		$connection = $connections->connection($this->input->getOption('database'));
		$path = $this->path($connection);

		$this->schemaState($connection)->dump($connection, $path);

		$dispatcher->dispatch(new SchemaDumped($connection, $path));

		$info = 'Database schema dumped';

		if ($this->option('prune')) {
			(new Filesystem)->deleteDirectory(database_path('migrations'), false);

			$info .= ' and pruned';
		}

		$this->components->info($info . ' successfully.');
	}

	/**
	 * Get the path that the dump should be written to.
	 */
	protected function path(Connection $connection): string
	{
		return tap(
			$this->option('path') ?: database_path('schema/' . $connection->getName() . '-schema.sql'),
			fn ($path) => (new Filesystem)->ensureDirectoryExists(dirname($path))
		);
	}

	/**
	 * Create a schema state instance for the given connection.
	 */
	protected function schemaState(Connection $connection): mixed
	{
		$migrations = config('database.migrations', 'migrations');

		$migrationTable = is_array($migrations)
			? $migrations['table'] ?? 'migrations'
			: $migrations;

		return $connection->getSchemaState()
			->withMigrationTable($migrationTable)
			->handleOutputUsing(fn ($type, $buffer) => $this->output->write($buffer));
	}
}
