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
