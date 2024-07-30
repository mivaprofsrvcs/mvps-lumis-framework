<?php

namespace MVPS\Lumis\Framework\Routing;

class RouteFileRegistrar
{
	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * Create a new route file registrar instance.
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * Require the given routes file.
	 */
	public function register(string $routes): void
	{
		require $routes;
	}
}
