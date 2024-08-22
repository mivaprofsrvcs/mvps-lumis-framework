<?php

namespace MVPS\Lumis\Framework\Routing\Middleware;

use Generator;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Routing\Router;

class SortedMiddleware extends Collection
{
	/**
	 * Create a new sorted middleware container.
	 */
	public function __construct(array $priorityMap, Collection|array $middlewares)
	{
		if ($middlewares instanceof Collection) {
			$middlewares = $middlewares->all();
		}

		$this->items = $this->sortMiddleware($priorityMap, $middlewares);
	}

	/**
	 * Resolve the middleware names to look for in the priority array.
	 */
	protected function middlewareNames(string $middleware): Generator
	{
		$stripped = head(explode(':', $middleware));

		yield $stripped;

		$interfaces = @class_implements($stripped);

		if ($interfaces !== false) {
			foreach ($interfaces as $interface) {
				yield $interface;
			}
		}

		$parents = @class_parents($stripped);

		if ($parents !== false) {
			foreach ($parents as $parent) {
				yield $parent;
			}
		}
	}

	/**
	 * Splice a middleware into a new position and remove the old entry.
	 */
	protected function moveMiddleware(array $middlewares, int $from, int $to): array
	{
		array_splice($middlewares, $to, 0, $middlewares[$from]);

		unset($middlewares[$from + 1]);

		return $middlewares;
	}

	/**
	 * Calculate the priority map index of the middleware.
	 */
	protected function priorityMapIndex(array $priorityMap, string $middleware): int|null
	{
		foreach ($this->middlewareNames($middleware) as $name) {
			$priorityIndex = array_search($name, $priorityMap);

			if ($priorityIndex !== false) {
				return $priorityIndex;
			}
		}

		return null;
	}

	/**
	 * Sort the middlewares by the given priority map.
	 *
	 * Each call to this method makes one discrete middleware movement if necessary.
	 */
	protected function sortMiddleware(array $priorityMap, array $middlewares): array
	{
		$lastIndex = 0;

		foreach ($middlewares as $index => $middleware) {
			if (! is_string($middleware)) {
				continue;
			}

			$priorityIndex = $this->priorityMapIndex($priorityMap, $middleware);

			if (! is_null($priorityIndex)) {
				// If the middleware is in the priority map and a lower-priority
				// middleware has been encountered previously, reposition the
				// current middleware to a higher  priority within the
				// middleware array.
				if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
					return $this->sortMiddleware(
						$priorityMap,
						array_values($this->moveMiddleware($middlewares, $index, $lastIndex))
					);
				}

				// This middleware is the first encountered from the priority
				// map. Save its current and priority map index for comparison
				// in subsequent iterations.
				$lastIndex = $index;

				$lastPriorityIndex = $priorityIndex;
			}
		}

		return Router::uniqueMiddleware($middlewares);
	}
}
