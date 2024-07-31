<?php

namespace MVPS\Lumis\Framework\Http\Client\Promises;

class Utilities
{
	/**
	 * Get the global task queue used for promise resolution.
	 */
	public static function queue(TaskQueue|null $assign = null): TaskQueue
	{
		static $queue;

		if ($assign) {
			$queue = $assign;
		} elseif (! $queue) {
			$queue = new TaskQueue();
		}

		return $queue;
	}
}
