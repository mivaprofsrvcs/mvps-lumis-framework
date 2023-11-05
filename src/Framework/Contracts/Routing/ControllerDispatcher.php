<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use MVPS\Lumis\Framework\Routing\Route;

interface ControllerDispatcher
{
	/**
	 * Dispatch a request to a given controller and method.
	 */
	public function dispatch(Route $route, mixed $controller, string $method): mixed;

	/**
	 * TODO: Implement if/when adding middleware support
	 *
	 * Get the middleware for the controller instance.
	 */
	//public function getMiddleware(Controller $controller, string $method): array;
}
