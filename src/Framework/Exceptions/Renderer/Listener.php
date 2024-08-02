<?php

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use Illuminate\Database\Events\QueryExecuted;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;

class Listener
{
	/**
	 * The queries that have been executed.
	 *
	 * @var array<int, array{connectionName: string, time: float, sql: string, bindings: array}>
	 */
	protected array $queries = [];

	/**
	 * Listens for the query executed event.
	 */
	public function onQueryExecuted(QueryExecuted $event): void
	{
		if (count($this->queries) === 100) {
			return;
		}

		$this->queries[] = [
			'connectionName' => $event->connectionName,
			'time' => $event->time,
			'sql' => $event->sql,
			'bindings' => $event->bindings,
		];
	}

	/**
	 * Returns the queries that have been executed.
	 */
	public function queries(): array
	{
		return $this->queries;
	}

	/**
	 * Register the appropriate listeners on the given event dispatcher.
	 */
	public function registerListeners(Dispatcher $events): void
	{
		$events->listen(QueryExecuted::class, [$this, 'onQueryExecuted']);

		// TODO: Implement with queue system
		// $events->listen([JobProcessing::class, JobProcessed::class], function () {
		// 	$this->queries = [];
		// });
	}
}
