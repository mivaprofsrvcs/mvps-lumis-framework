<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class CheckResponseForModifications
{
	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		$response = $next($request);

		if ($response instanceof Response && $response->isNotModified($request)) {
			$response = $response->withNotModified();
		}

		return $response;
	}
}
