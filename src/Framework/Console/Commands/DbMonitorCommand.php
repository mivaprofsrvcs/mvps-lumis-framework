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
use Illuminate\Database\Events\DatabaseBusy;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\DatabaseInspectionCommand;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:monitor')]
class DbMonitorCommand extends DatabaseInspectionCommand
{
	/**
	 * The connection resolver instance.
	 *
	 * @var \Illuminate\Database\ConnectionResolverInterface
	 */
	protected ConnectionResolverInterface $connection;

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'db:monitor
		{--databases= : The database connections to monitor}
		{--max= : The maximum number of connections that can be open before an event is dispatched}';

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Monitor the number of connections on the specified database';

	/**
	 * The events dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected Dispatcher $events;

	/**
	 * Create a database monitor command instance.
	 */
	public function __construct(ConnectionResolverInterface $connection, Dispatcher $events)
	{
		parent::__construct();

		$this->connection = $connection;
		$this->events = $events;
	}

	/**
	 * Display the databases and their connection counts in the console.
	 */
	protected function displayConnections(Collection $databases): void
	{
		$this->newLine();

		$this->components->twoColumnDetail('<fg=gray>Database name</>', '<fg=gray>Connections</>');

		$databases->each(function ($database) {
			$status = '[' . $database['connections'] . '] ' . $database['status'];

			$this->components->twoColumnDetail($database['database'], $status);
		});

		$this->newLine();
	}

	/**
	 * Dispatch the database monitoring events.
	 */
	protected function dispatchEvents(Collection $databases): void
	{
		$databases->each(function ($database) {
			if ($database['status'] === '<fg=green;options=bold>OK</>') {
				return;
			}

			$this->events->dispatch(
				new DatabaseBusy($database['database'], $database['connections'])
			);
		});
	}

	/**
	 * Execute the database monitor command.
	 */
	public function handle(): void
	{
		$databases = $this->parseDatabases($this->option('databases') ?? '');

		$this->displayConnections($databases);

		if ($this->option('max')) {
			$this->dispatchEvents($databases);
		}
	}

	/**
	 * Parse the database into an array of the connections.
	 */
	protected function parseDatabases(string $databases): Collection
	{
		return collection(explode(',', $databases))
			->map(function ($database) {
				if (! $database) {
					$database = $this->lumis['config']['database.default'];
				}

				$maxConnections = $this->option('max');
				$connections = $this->getConnectionCount($this->connection->connection($database));

				return [
					'database' => $database,
					'connections' => $connections,
					'status' => $maxConnections && $connections >= $maxConnections
						? '<fg=yellow;options=bold>ALERT</>'
						: '<fg=green;options=bold>OK</>',
				];
			});
	}
}
