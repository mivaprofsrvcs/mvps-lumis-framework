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

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Number;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\DatabaseInspectionCommand;
use MVPS\Lumis\Framework\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:show')]
class DbShowCommand extends DatabaseInspectionCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Display information about the given database';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'db:show
		{--database= : The database connection}
		{--json : Output the database information as JSON}
		{--counts : Show the table row count <bg=red;options=bold> Note: This can be slow on large databases </>}
		{--views : Show the database views <bg=red;options=bold> Note: This can be slow on large databases </>}
		{--types : Show the user defined types}';


	/**
	 * Render the database information.
	 */
	protected function display(array $data): void
	{
		$this->option('json')
			? $this->displayJson($data)
			: $this->displayForCli($data);
	}

	/**
	 * Render the database information formatted for the CLI.
	 */
	protected function displayForCli(array $data): void
	{
		$platform = $data['platform'];
		$tables = $data['tables'];
		$views = $data['views'] ?? null;
		$types = $data['types'] ?? null;

		$this->newLine();

		$this->components->twoColumnDetail('<fg=green;options=bold>' . $platform['name'] . '</>', $platform['version']);
		$this->components->twoColumnDetail('Database', Arr::get($platform['config'], 'database'));
		$this->components->twoColumnDetail('Host', Arr::get($platform['config'], 'host'));
		$this->components->twoColumnDetail('Port', Arr::get($platform['config'], 'port'));
		$this->components->twoColumnDetail('Username', Arr::get($platform['config'], 'username'));
		$this->components->twoColumnDetail('URL', Arr::get($platform['config'], 'url'));
		$this->components->twoColumnDetail('Open Connections', $platform['open_connections']);
		$this->components->twoColumnDetail('Tables', $tables->count());

		if ($tableSizeSum = $tables->sum('size')) {
			$this->components->twoColumnDetail('Total Size', Number::fileSize($tableSizeSum, 2));
		}

		$this->newLine();

		if ($tables->isNotEmpty()) {
			$hasSchema = ! is_null($tables->first()['schema']);

			$this->components->twoColumnDetail(
				($hasSchema
					? '<fg=green;options=bold>Schema</> <fg=gray;options=bold>/</> '
					: '') . '<fg=green;options=bold>Table</>',
				'Size' . ($this->option('counts') ? ' <fg=gray;options=bold>/</> <fg=yellow;options=bold>Rows</>' : '')
			);

			$tables->each(function ($table) {
				if ($tableSize = $table['size']) {
					$tableSize = Number::fileSize($tableSize, 2);
				}

				$this->components->twoColumnDetail(
					($table['schema'] ? $table['schema'] . ' <fg=gray;options=bold>/</> ' : '') .
						$table['table'] . ($this->output->isVerbose() ? ' <fg=gray>' . $table['engine'] . '</>' : null),
					($tableSize ?: '—') . ($this->option('counts')
						? ' <fg=gray;options=bold>/</> <fg=yellow;options=bold>' .
							Number::format($table['rows']) . '</>'
						: '')
				);

				if ($this->output->isVerbose()) {
					if ($table['comment']) {
						$this->components->bulletList([
							$table['comment'],
						]);
					}
				}
			});

			$this->newLine();
		}

		if ($views && $views->isNotEmpty()) {
			$hasSchema = ! is_null($views->first()['schema']);

			$this->components->twoColumnDetail(
				($hasSchema ? '<fg=green;options=bold>Schema</> <fg=gray;options=bold>/</> ' : '') .
					'<fg=green;options=bold>View</>',
				'<fg=green;options=bold>Rows</>'
			);

			$views->each(fn ($view) => $this->components->twoColumnDetail(
				($view['schema'] ? $view['schema'] . ' <fg=gray;options=bold>/</> ' : '') . $view['view'],
				Number::format($view['rows'])
			));

			$this->newLine();
		}

		if ($types && $types->isNotEmpty()) {
			$hasSchema = ! is_null($types->first()['schema']);

			$this->components->twoColumnDetail(
				($hasSchema ? '<fg=green;options=bold>Schema</> <fg=gray;options=bold>/</> ' : '') .
					'<fg=green;options=bold>Type</>',
				'<fg=green;options=bold>Type</> <fg=gray;options=bold>/</> <fg=green;options=bold>Category</>'
			);

			$types->each(fn ($type) => $this->components->twoColumnDetail(
				($type['schema'] ? $type['schema'] . ' <fg=gray;options=bold>/</> ' : '') . $type['name'],
				$type['type'] . ' <fg=gray;options=bold>/</> ' . $type['category']
			));

			$this->newLine();
		}
	}

	/**
	 * Render the database information as JSON.
	 */
	protected function displayJson(array $data): void
	{
		$this->output->writeln(json_encode($data));
	}

	/**
	 * Execute the console command.
	 */
	public function handle(ConnectionResolverInterface $connections): int
	{
		$database = $this->input->getOption('database');

		$connection = $connections->connection();

		$schema = $connection->getSchemaBuilder();

		$data = [
			'platform' => [
				'config' => $this->getConfigFromDatabase($database),
				'name' => $this->getConnectionName($connection, $database ?? ''),
				'version' => $connection->getServerVersion(),
				'open_connections' => $this->getConnectionCount($connection),
			],
			'tables' => $this->tables($connection, $schema),
		];

		if ($this->option('views')) {
			$data['views'] = $this->views($connection, $schema);
		}

		if ($this->option('types')) {
			$data['types'] = $this->types($connection, $schema);
		}

		$this->display($data);

		return 0;
	}

	/**
	 * Get information regarding the tables within the database.
	 */
	protected function tables(ConnectionInterface $connection, Builder $schema): Collection
	{
		return collection($schema->getTables())->map(fn ($table) => [
			'table' => $table['name'],
			'schema' => $table['schema'],
			'size' => $table['size'],
			'rows' => $this->option('counts') ? $connection->table($table['name'])->count() : null,
			'engine' => $table['engine'],
			'collation' => $table['collation'],
			'comment' => $table['comment'],
		]);
	}

	/**
	 * Get information regarding the user-defined types within the database.
	 */
	protected function types(ConnectionInterface $connection, Builder $schema)
	{
		return collection($schema->getTypes())
			->map(fn ($type) => [
				'name' => $type['name'],
				'schema' => $type['schema'],
				'type' => $type['type'],
				'category' => $type['category'],
			]);
	}

	/**
	 * Get information regarding the views within the database.
	 */
	protected function views(ConnectionInterface $connection, Builder $schema): Collection
	{
		return collection($schema->getViews())
			->reject(fn ($view) => str($view['name'])->startsWith(['pg_catalog', 'information_schema', 'spt_']))
			->map(fn ($view) => [
				'view' => $view['name'],
				'schema' => $view['schema'],
				'rows' => $connection->table($view->getName())->count(),
			]);
	}
}
