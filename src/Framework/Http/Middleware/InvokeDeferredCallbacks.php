<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class InvokeDeferredCallbacks
{
	/**
	 * Handle the incoming request.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		return $next($request);
	}

	/**
	 * Invoke the deferred callbacks.
	 */
	public function terminate(Request $request, Response $response): void
	{
		Container::getInstance()
			->make(DeferredCallbackCollection::class)
			->invokeWhen(fn ($callback) => $response->getStatusCode() < 400 || $callback->always);
	}
}
