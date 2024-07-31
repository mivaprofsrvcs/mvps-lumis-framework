<?php

namespace MVPS\Lumis\Framework\Contracts\Routing\Matching;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

interface Validator
{
	/**
	 * Validate a given rule against a route and request.
	 */
	public function matches(Route $route, Request $request): bool;
}
