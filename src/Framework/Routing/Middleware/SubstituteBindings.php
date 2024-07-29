<?php

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
			$this->router->substituteBindings($route = $request->route());

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
