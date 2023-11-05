<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Routing\Traits\ResolvesRouteDependencies;

class ControllerDispatcher implements ControllerDispatcherContract
{
	use ResolvesRouteDependencies;

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
		return $this->resolveClassMethodDependencies($route->getParametersWithoutNulls(), $controller, $method);
	}
}
