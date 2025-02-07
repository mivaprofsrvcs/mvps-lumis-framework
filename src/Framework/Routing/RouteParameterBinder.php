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

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Http\Request;

class RouteParameterBinder
{
	/**
	 * The route instance.
	 *
	 * @var MVPS\Lumis\Framework\Routing\Route
	 */
	protected Route $route;

	/**
	 * Create a new Route parameter binder instance.
	 */
	public function __construct(Route $route)
	{
		$this->route = $route;
	}

	/**
	 * Get the parameter matches for the path portion of the URI.
	 */
	protected function bindPathParameters(Request $request): array
	{
		$path = '/' . trim(rawurldecode($request->getPath()), '/');

		preg_match($this->route->compiled->getRegex(), $path, $matches);

		return $this->matchToKeys(array_slice($matches, 1));
	}

	/**
	 * Get the parameters for the route.
	 */
	public function parameters(Request $request): array
	{
		return $this->bindPathParameters($request);
	}

	/**
	 * Combine a set of parameter matches with the route's keys.
	 */
	protected function matchToKeys(array $matches): array
	{
		$parameterNames = $this->route->parameterNames();

		if (empty($parameterNames)) {
			return [];
		}

		$parameters = array_intersect_key($matches, array_flip($parameterNames));

		return array_filter($parameters, function ($value) {
			return is_string($value) && strlen($value) > 0;
		});
	}
}
