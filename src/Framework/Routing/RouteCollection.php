<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Http\Request;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCollection extends AbstractRouteCollection
{
	/**
	 * A look-up table of routes by controller action.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Route[]
	 */
	protected array $actionList = [];

	/**
	 * A flattened array of all of the routes.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Route[]
	 */
	protected array $allRoutes = [];

	/**
	 * A look-up table of routes by their names.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Route[]
	 */
	protected array $nameList = [];

	/**
	 * A list of the routes keyed by method.
	 *
	 * @var array
	 */
	protected array $routes = [];

	/**
	 * Add a Route instance to the collection.
	 */
	public function add(Route $route): Route
	{
		$this->addToCollections($route);

		$this->addLookups($route);

		return $route;
	}

	/**
	 * Add the route to any look-up tables if necessary.
	 */
	protected function addLookups(Route $route): void
	{
		// If the route has a name, we add it to the name lookup table. This
		// allows us to quickly find any route associated with a name without
		// having to iterate through every route each time we perform a lookup.
		if ($name = $route->getName()) {
			$this->nameList[$name] = $route;
		}

		// If the route points to a controller, we store the associated action.
		// This enables reverse routing to controllers during request processing
		// and facilitates easy URL generation for the specified controllers.
		$action = $route->getAction();

		if (isset($action['controller'])) {
			$this->addToActionList($action, $route);
		}
	}

	/**
	 * Add a route to the controller action dictionary.
	 */
	protected function addToActionList(array $action, Route $route): void
	{
		$this->actionList[trim($action['controller'], '\\')] = $route;
	}

	/**
	 * Add the given route to the arrays of routes.
	 */
	protected function addToCollections(Route $route): void
	{
		$domainAndUri = $route->getDomain() . $route->uri();

		foreach ($route->methods() as $method) {
			$this->routes[$method][$domainAndUri] = $route;
		}

		$this->allRoutes[$method . $domainAndUri] = $route;
	}

	/**
	 * Get routes from the collection by method.
	 */
	public function get(string|null $method = null): array
	{
		return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
	}

	/**
	 * Get a route instance by its controller action.
	 */
	public function getByAction(string $action): Route|null
	{
		return $this->actionList[$action] ?? null;
	}

	/**
	 * Get a route instance by its name.
	 */
	public function getByName(string $name): Route|null
	{
		return $this->nameList[$name] ?? null;
	}

	/**
	 * Get all of the routes in the collection.
	 */
	public function getRoutes(): array
	{
		return array_values($this->allRoutes);
	}

	/**
	 * Get all of the routes keyed by their HTTP verb / method.
	 */
	public function getRoutesByMethod(): array
	{
		return $this->routes;
	}

	/**
	 * Get all of the routes keyed by their name.
	 */
	public function getRoutesByName(): array
	{
		return $this->nameList;
	}

	/**
	 * Determine if the route collection contains a given named route.
	 */
	public function hasNamedRoute(string $name): bool
	{
		return ! is_null($this->getByName($name));
	}

	/**
	 * Find the first route matching a given request.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\MethodNotAllowedHttpException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundHttpException
	 */
	public function match(Request $request): Route
	{
		$routes = $this->get($request->getMethod());

		// Attempt to find a matching route for the current request method.
		// If a matching route is found, return it for the consumer to call.
		// If no match is found, proceed to check routes with a different verb.
		$route = $this->matchAgainstRoutes($routes, $request);

		return $this->handleMatchedRoute($request, $route);
	}

	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any actions are overwritten with new controllers.
	 */
	public function refreshActionLookups(): void
	{
		$this->actionList = [];

		foreach ($this->allRoutes as $route) {
			if (isset($route->getAction()['controller'])) {
				$this->addToActionList($route->getAction(), $route);
			}
		}
	}

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined or if routes are overwritten.
	 */
	public function refreshNameLookups(): void
	{
		$this->nameList = [];

		foreach ($this->allRoutes as $route) {
			if ($route->getName()) {
				$this->nameList[$route->getName()] = $route;
			}
		}
	}

	/**
	 * Convert the collection to a Symfony RouteCollection instance.
	 */
	public function toSymfonyRouteCollection(): SymfonyRouteCollection
	{
		$symfonyRoutes = parent::toSymfonyRouteCollection();

		$this->refreshNameLookups();

		return $symfonyRoutes;
	}
}
