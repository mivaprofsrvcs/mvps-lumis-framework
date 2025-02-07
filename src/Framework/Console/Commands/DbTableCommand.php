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

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Number;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\DatabaseInspectionCommand;
use MVPS\Lumis\Framework\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand(name: 'db:table')]
class DbTableCommand extends DatabaseInspectionCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Display information about the given database table';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'db:table
		{table? : The name of the table}
		{--database= : The database connection}
		{--json : Output the table information as JSON}';

	/**
	 * Get the information regarding the table's columns.
	 */
	protected function columns(Builder $schema, string $table): Collection
	{
		return collection($schema->getColumns($table))
			->map(fn ($column) => [
				'column' => $column['name'],
				'attributes' => $this->getAttributesForColumn($column),
				'default' => $column['default'],
				'type' => $column['type'],
			]);
	}

	/**
	 * Render the table information.
	 */
	protected function display(array $data): void
	{
		$this->option('json')
			? $this->displayJson($data)
			: $this->displayForCli($data);
	}

	/**
	 * Render the table information formatted for the CLI.
	 */
	protected function displayForCli(array $data): void
	{
		[$table, $columns, $indexes, $foreignKeys] = [
			$data['table'],
			$data['columns'],
			$data['indexes'],
			$data['foreign_keys'],
		];

		$this->newLine();

		$this->components->twoColumnDetail('<fg=green;options=bold>' . $table['name'] . '</>');
		$this->components->twoColumnDetail('Columns', $table['columns']);

		if ($size = $table['size']) {
			$this->components->twoColumnDetail('Size', Number::fileSize($size, 2));
		}

		$this->newLine();

		if ($columns->isNotEmpty()) {
			$this->components->twoColumnDetail('<fg=green;options=bold>Column</>', 'Type');

			$columns->each(function ($column) {
				$this->components->twoColumnDetail(
					$column['column'] . ' <fg=gray>' . $column['attributes']->implode(', ') . '</>',
					(! is_null($column['default']) ? '<fg=gray>' . $column['default'] . '</> ' : '') . $column['type']
				);
			});

			$this->newLine();
		}

		if ($indexes->isNotEmpty()) {
			$this->components->twoColumnDetail('<fg=green;options=bold>Index</>');

			$indexes->each(function ($index) {
				$this->components->twoColumnDetail(
					$index['name'] . ' <fg=gray>' . $index['columns']->implode(', ') . '</>',
					$index['attributes']->implode(', ')
				);
			});

			$this->newLine();
		}

		if ($foreignKeys->isNotEmpty()) {
			$this->components->twoColumnDetail('<fg=green;options=bold>Foreign Key</>', 'On Update / On Delete');

			$foreignKeys->each(function ($foreignKey) {
				$this->components->twoColumnDetail(
					$foreignKey['name'] . ' <fg=gray;options=bold>' . $foreignKey['columns']->implode(', ') .
						' references ' . $foreignKey['foreign_columns']->implode(', ') . ' on ' .
						$foreignKey['foreign_table'] . '</>',
					$foreignKey['on_update'] . ' / ' . $foreignKey['on_delete'],
				);
			});

			$this->newLine();
		}
	}

	/**
	 * Render the table information as JSON.
	 */
	protected function displayJson(array $data): void
	{
		$this->output->writeln(json_encode($data));
	}

	/**
	 * Get the information regarding the table's foreign keys.
	 */
	protected function foreignKeys(Builder $schema, string $table): Collection
	{
		return collection($schema->getForeignKeys($table))
			->map(fn ($foreignKey) => [
				'name' => $foreignKey['name'],
				'columns' => collection($foreignKey['columns']),
				'foreign_schema' => $foreignKey['foreign_schema'],
				'foreign_table' => $foreignKey['foreign_table'],
				'foreign_columns' => collection($foreignKey['foreign_columns']),
				'on_update' => $foreignKey['on_update'],
				'on_delete' => $foreignKey['on_delete'],
			]);
	}

	/**
	 * Get the attributes for a table column.
	 */
	protected function getAttributesForColumn(array $column): Collection
	{
		return collection([
			$column['type_name'],
			$column['auto_increment'] ? 'autoincrement' : null,
			$column['nullable'] ? 'nullable' : null,
			$column['collation'],
		])->filter();
	}

	/**
	 * Get the attributes for a table index.
	 */
	protected function getAttributesForIndex(array $index): Collection
	{
		return collection([
			$index['type'],
			count($index['columns']) > 1 ? 'compound' : null,
			$index['unique'] && ! $index['primary'] ? 'unique' : null,
			$index['primary'] ? 'primary' : null,
		])->filter();
	}

	/**
	 * Execute the database table console command.
	 */
	public function handle(ConnectionResolverInterface $connections): int
	{
		$connection = $connections->connection($this->input->getOption('database'));
		$schema = $connection->getSchemaBuilder();
		$tables = $schema->getTables();

		$tableName = $this->argument('table') ?: select(
			'Which table would you like to inspect?',
			array_column($tables, 'name')
		);

		$table = Arr::first($tables, fn ($table) => $table['name'] === $tableName);

		if (! $table) {
			$this->components->warn("Table [{$tableName}] doesn't exist.");

			return 1;
		}

		$tableName = $this->withoutTablePrefix($connection, $table['name']);

		$columns = $this->columns($schema, $tableName);
		$indexes = $this->indexes($schema, $tableName);
		$foreignKeys = $this->foreignKeys($schema, $tableName);

		$data = [
			'table' => [
				'name' => $table['name'],
				'columns' => count($columns),
				'size' => $table['size'],
			],
			'columns' => $columns,
			'indexes' => $indexes,
			'foreign_keys' => $foreignKeys,
		];

		$this->display($data);

		return 0;
	}

	/**
	 * Get the information regarding the table's indexes.
	 */
	protected function indexes(Builder $schema, string $table): Collection
	{
		return collection($schema->getIndexes($table))
			->map(fn ($index) => [
				'name' => $index['name'],
				'columns' => collection($index['columns']),
				'attributes' => $this->getAttributesForIndex($index),
			]);
	}
}
