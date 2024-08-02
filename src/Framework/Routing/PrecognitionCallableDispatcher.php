<?php

namespace MVPS\Lumis\Framework\Routing;

class PrecognitionCallableDispatcher extends CallableDispatcher
{
	/**
	 * Dispatch a request to a given callable.
	 */
	public function dispatch(Route $route, callable $callable): mixed
	{
		$this->resolveParameters($route, $callable);

		abort(204, headers: ['Precognition-Success' => 'true']);
	}
}
