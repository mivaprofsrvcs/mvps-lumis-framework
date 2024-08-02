<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

interface RouteCollection
{
	/**
	 * Add a Route instance to the collection.
	 */
	public function add(Route $route): Route;

	/**
	 * Get routes from the collection by method.
	 */
	public function get(string|null $method = null): array;

	/**
	 * Get a route instance by its controller action.
	 */
	public function getByAction(string $action): Route|null;

	/**
	 * Get a route instance by its name.
	 */
	public function getByName(string $name): Route|null;

	/**
	 * Get all of the routes in the collection.
	 */
	public function getRoutes(): array;

	/**
	 * Get all of the routes keyed by their name.
	 */
	public function getRoutesByName(): array;

	/**
	 * Get all of the routes keyed by their HTTP verb / method.
	 */
	public function getRoutesByMethod(): array;

	/**
	 * Determine if the route collection contains a given named route.
	 */
	public function hasNamedRoute(string $name): bool;

	/**
	 * Find the first route matching a given request.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\MethodNotAllowedException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundException
	 */
	public function match(Request $request): Route;

	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any actions are overwritten with new controllers.
	 */
	public function refreshActionLookups(): void;

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined or if routes are overwritten.
	 */
	public function refreshNameLookups(): void;
}
