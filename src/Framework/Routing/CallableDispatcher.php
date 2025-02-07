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

use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\CallableDispatcher as CallableDispatcherContract;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Routing\Traits\ResolvesRouteDependencies;
use ReflectionFunction;

class CallableDispatcher implements CallableDispatcherContract
{
	use ResolvesRouteDependencies;

	/**
	 * The container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected Container $container;

	/**
	 * Create a new callable dispatcher instance.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Dispatch a request to a given callable.
	 */
	public function dispatch(Route $route, callable $callable): mixed
	{
		return $callable(...array_values($this->resolveParameters($route, $callable)));
	}

	/**
	 * Resolve the parameters for the callable.
	 */
	protected function resolveParameters(Route $route, callable $callable): array
	{
		return $this->resolveMethodDependencies($route->parametersWithoutNulls(), new ReflectionFunction($callable));
	}
}
