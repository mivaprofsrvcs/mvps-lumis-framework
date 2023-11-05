<?php

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
		return $this->resolveMethodDependencies($route->getParametersWithoutNulls(), new ReflectionFunction($callable));
	}
}
