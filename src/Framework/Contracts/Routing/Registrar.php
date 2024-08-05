<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use MVPS\Lumis\Framework\Routing\PendingResourceRegistration;
use MVPS\Lumis\Framework\Routing\Route;

interface Registrar
{
	/**
	 * Register a new DELETE route with the router.
	 */
	public function delete(string $uri, array|callable|string|null $action): Route;

	/**
	 * Register a new GET route with the router.
	 */
	public function get(string $uri, array|callable|string|null $action): Route;

	/**
	 * Create a route group with shared attributes.
	 */
	// public function group(array $attributes, Closure|string $routes): void;

	/**
	 * Register a new route with the given verbs.
	 */
	public function match(array|string $methods, string $uri, array|string|callable|null $action): Route;

	/**
	 * Register a new OPTIONS route with the router.
	 */
	public function options(string $uri, array|callable|string|null $action): Route;

	/**
	 * Register a new PATCH route with the router.
	 */
	public function patch(string $uri, array|callable|string|null $action): Route;

	/**
	 * Register a new POST route with the router.
	 */
	public function post(string $uri, array|callable|string|null $action): Route;

	/**
	 * Register a new PUT route with the router.
	 */
	public function put(string $uri, array|callable|string|null $action): Route;

	/**
	 * Route a resource to a controller.
	 */
	public function resource(string $name, string $controller, array $options = []): PendingResourceRegistration;

	/**
	 * Substitute the route bindings onto the route.
	 */
	public function substituteBindings(Route $route): Route;

	/**
	 * Substitute the implicit Eloquent model bindings for the route.
	 */
	public function substituteImplicitBindings(Route $route): mixed;
}
