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

namespace MVPS\Lumis\Framework\Http\Client\Promises;

class TaskQueue
{
	/**
	 * Whether to automatically run the queue on shutdown.
	 *
	 * @var bool
	 */
	protected bool $enableShutdown = true;

	/**
	 * The queue of tasks to be executed.
	 *
	 * @var array<callable>
	 */
	protected array $queue = [];

	/**
	 * Creates a new task queue instance.
	 *
	 * Optionally registers a shutdown function to run the queue automatically.
	 */
	public function __construct(bool $withShutdown = true)
	{
		if ($withShutdown) {
			register_shutdown_function(function (): void {
				if ($this->enableShutdown) {
					$err = error_get_last();

					if (! $err || ($err['type'] ^ E_ERROR)) {
						$this->run();
					}
				}
			});
		}
	}

	/**
	 * Adds a new task to the queue.
	 */
	public function add(callable $task): void
	{
		$this->queue[] = $task;
	}

	/**
	 * The task queue will be run and exhausted by default when the process
	 * exits IFF the exit is not the result of a PHP E_ERROR error.
	 *
	 * You can disable running the automatic shutdown of the queue by calling
	 * this function. If you disable the task queue shutdown process, then you
	 * MUST either run the task queue (as a result of running your event loop
	 * or manually using the run() method) or wait on each outstanding promise.
	 *
	 * Note: This shutdown will occur before any destructors are triggered.
	 */
	public function disableShutdown(): void
	{
		$this->enableShutdown = false;
	}

	/**
	 * Checks if the queue is empty.
	 */
	public function isEmpty(): bool
	{
		return ! $this->queue;
	}

	/**
	 * Executes all tasks in the queue.
	 */
	public function run(): void
	{
		while ($task = array_shift($this->queue)) {
			$task();
		}
	}
}
