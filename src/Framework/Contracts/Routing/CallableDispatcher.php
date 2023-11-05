<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use MVPS\Lumis\Framework\Routing\Route;

interface CallableDispatcher
{
	/**
	 * Dispatch a request to a given callable.
	 */
	public function dispatch(Route $route, callable $callable): mixed;
}
