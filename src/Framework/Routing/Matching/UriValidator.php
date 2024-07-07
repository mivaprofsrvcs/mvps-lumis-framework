<?php

namespace MVPS\Lumis\Framework\Routing\Matching;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

class UriValidator implements ValidatorInterface
{
	/**
	 * Validate a given rule against a route and request.
	 */
	public function matches(Route $route, Request $request): bool
	{
		$path = rtrim($request->getUri()->getPath(), '/') ?: '/';

		return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
	}
}
