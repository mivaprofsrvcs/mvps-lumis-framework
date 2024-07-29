<?php

namespace MVPS\Lumis\Framework\Routing\Matching;

use MVPS\Lumis\Framework\Contracts\Routing\Matching\Validator;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

class MethodValidator implements Validator
{
	/**
	 * Validate a given rule against a route and request.
	 */
	public function matches(Route $route, Request $request): bool
	{
		return in_array($request->getMethod(), $route->methods());
	}
}
