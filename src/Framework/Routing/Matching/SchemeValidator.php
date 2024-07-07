<?php

namespace MVPS\Lumis\Framework\Routing\Matching;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

class SchemeValidator implements ValidatorInterface
{
	/**
	 * Validate a given rule against a route and request.
	 */
	public function matches(Route $route, Request $request): bool
	{
		if ($route->isHttpOnly()) {
			return ! $request->isSecure();
		} elseif ($route->isSecure()) {
			return $request->isSecure();
		}

		return true;
	}
}
