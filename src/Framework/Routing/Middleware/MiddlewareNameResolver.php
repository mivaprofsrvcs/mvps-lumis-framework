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

namespace MVPS\Lumis\Framework\Routing\Middleware;

use Closure;

class MiddlewareNameResolver
{
	/**
	 * Parse the middleware group and format it for usage.
	 */
	protected static function parseMiddlewareGroup(string $name, array $map, array $middlewareGroups): array
	{
		$results = [];

		foreach ($middlewareGroups[$name] as $middleware) {
			// If the middleware is a group, merge its middleware into the
			// results. This  allows for convenient grouping and re-use of
			// middleware without repetition.
			if (isset($middlewareGroups[$middleware])) {
				$results = array_merge(
					$results,
					static::parseMiddlewareGroup($middleware, $map, $middlewareGroups)
				);

				continue;
			}

			[$middleware, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

			// If the middleware is a route middleware, extract the full class
			// name from the list. Re-append parameters to the class name for
			// correct pipeline extraction.
			if (isset($map[$middleware])) {
				$middleware = $map[$middleware];
			}

			$results[] = $middleware . ($parameters ? ':' . $parameters : '');
		}

		return $results;
	}

	/**
	 * Resolve the middleware name to a class name(s) preserving passed parameters.
	 */
	public static function resolve(Closure|string $name, array $map, array $middlewareGroups): Closure|string|array
	{
		// If the middleware is a Closure, return it directly for inline
		// registration. This allows developers to experiment with middleware
		// without defining a class.
		if ($name instanceof Closure) {
			return $name;
		}

		if (isset($map[$name]) && $map[$name] instanceof Closure) {
			return $map[$name];
		}

		// If the given middleware is a group name, return the corresponding
		// middleware  array. This enables grouping related middleware under
		// a single key for  convenient referencing.
		if (isset($middlewareGroups[$name])) {
			return static::parseMiddlewareGroup($name, $map, $middlewareGroups);
		}

		// If the middleware is a string representing a class name, parse it to
		// obtain  the full class name and parameters. Create a pipeline
		// instance to handle  the middleware execution.
		[$name, $parameters] = array_pad(explode(':', $name, 2), 2, null);

		return ($map[$name] ?? $name) . (! is_null($parameters) ? ':' . $parameters : '');
	}
}
