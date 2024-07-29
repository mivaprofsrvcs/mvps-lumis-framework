<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Routing\Traits\FiltersControllerMiddleware;
use MVPS\Lumis\Framework\Routing\Traits\ResolvesRouteDependencies;

class ControllerDispatcher implements ControllerDispatcherContract
{
	use FiltersControllerMiddleware;
	use ResolvesRouteDependencies;

	/**
	 * The container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
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
	 * Dispatch a request to a given controller and method.
	 */
	public function dispatch(Route $route, mixed $controller, string $method): mixed
	{
		$parameters = $this->resolveParameters($route, $controller, $method);

		if (method_exists($controller, 'callAction')) {
			return $controller->callAction($method, $parameters);
		}

		return $controller->{$method}(...array_values($parameters));
	}

	/**
	 * Resolve the parameters for the controller.
	 */
	protected function resolveParameters(Route $route, mixed $controller, string $method): array
	{
		return $this->resolveClassMethodDependencies($route->parametersWithoutNulls(), $controller, $method);
	}

	/**
	 * Get the middleware for the controller instance.
	 */
	public function getMiddleware(Controller $controller, string $method): array
	{
		if (! method_exists($controller, 'getMiddleware')) {
			return [];
		}

		return collection($controller->getMiddleware())
			->reject(function ($data) use ($method) {
				return static::methodExcludedByOptions($method, $data['options']);
			})
			->pluck('middleware')
			->all();
	}
}
