<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Routing\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MVPS\Lumis\Framework\Contracts\Routing\Registrar;
use MVPS\Lumis\Framework\Http\Request;

class SubstituteBindings
{
	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Routing\Registrar
	 */
	protected Registrar $router;

	/**
	 * Create a new bindings substitutor instance.
	 */
	public function __construct(Registrar $router)
	{
		$this->router = $router;
	}

	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		try {
			$route = $request->route();

			$this->router->substituteBindings($route);

			$this->router->substituteImplicitBindings($route);
		} catch (ModelNotFoundException $exception) {
			if ($route->getMissing()) {
				return $route->getMissing()($request, $exception);
			}

			throw $exception;
		}

		return $next($request);
	}
}
