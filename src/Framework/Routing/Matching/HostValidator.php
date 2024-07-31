<?php

namespace MVPS\Lumis\Framework\Routing\Matching;

use MVPS\Lumis\Framework\Contracts\Routing\Matching\Validator;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

class HostValidator implements Validator
{
	/**
	 * Validate a given rule against a route and request.
	 */
	public function matches(Route $route, Request $request): bool
	{
		$hostRegex = $route->getCompiled()->getHostRegex();

		if (is_null($hostRegex)) {
			return true;
		}

		return preg_match($hostRegex, $request->getHost());
	}
}
