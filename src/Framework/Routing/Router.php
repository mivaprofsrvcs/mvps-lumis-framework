<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\ResponseFactory;
use MVPS\Lumis\Framework\Routing\Exceptions\MethodNotFoundException;
use MVPS\Lumis\Framework\Routing\Exceptions\RouteNotFoundException;

class Router
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected Container $container;

	/**
	 * The currently dispatched route instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Route|null
	 */
	protected Route|null $current = null;

	/**
	 * The request currently being dispatched.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request|null
	 */
	protected Request|null $currentRequest = null;

	/**
	 * The list of registered routes.
	 *
	 * @var array
	 */
	protected array $routes = [];

	/**
	 * Create a new Router instance.
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container ?: new Container;
	}

	/**
	 * Determine if the action is routing to a controller.
	 */
	protected function actionReferencesController(array|callable|string|null $action): bool
	{
		if (! $action instanceof Closure) {
			return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
		}

		return false;
	}

	/**
	 * Add a route to the routes list.
	 */
	public function addRoute(string $method, string $uri, array|callable|string|null $action): Route
	{
		if ($this->actionReferencesController($action)) {
			$action = $this->convertToControllerAction($action);
		}

		$route = $this->createRoute($method, $uri, $action);

		$this->routes[$method][$route->getUri()] = $route;

		return $route;
	}

	/**
	 * Add a controller based route action to an action.
	 */
	protected function convertToControllerAction(array|string $action): array
	{
		if (is_string($action)) {
			$action = ['uses' => $action];
		}

		$action['controller'] = $action['uses'];

		return $action;
	}

	/**
	 * Create a new Route instance.
	 */
	protected function createRoute(string $method, string $uri, array|callable|string|null $action): Route
	{
		return (new Route($method, $uri, $action))
			->setRouter($this)
			->setContainer($this->container);
	}

	/**
	 * Add a new DELETE route to the router.
	 */
	public function delete(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Dispatch the request to a route and return the response.
	 */
	public function dispatch(Request $request): Response
	{
		$this->currentRequest = $request;

		return $this->runRoute($request, $this->findRoute($request));
	}

	/**
	 * Find the route matching a given request.
	 *
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\MethodNotFoundException
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\RouteNotFoundException
	 */
	protected function findRoute(Request $request): Route
	{
		$method = $request->getMethod();
		$routes = $this->routes[$method] ?? null;

		if (is_null($routes)) {
			throw new MethodNotFoundException('No routes registered for method "' . $method . '"');
		}

		$matchedRoute = null;

		foreach ($routes as $route) {
			if ($route->matches($request)) {
				$matchedRoute = $route;

				break;
			}
		}

		if (! $matchedRoute instanceof Route) {
			throw new RouteNotFoundException(
				'No routes registered for method "' . $method . '" with uri "' . $request->getUri()->getPath() . '"'
			);
		}

		$matchedRoute->bind($request);

		$this->current = $matchedRoute;

		$matchedRoute->setContainer($this->container);

		$this->container->instance(Route::class, $matchedRoute);

		return $matchedRoute;
	}

	/**
	 * Add a new GET route to the router.
	 */
	public function get(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute('GET', $uri, $action);
	}

	/**
	 * Get the current dispatched route instance.
	 */
	public function getCurrent(): Route|null
	{
		return $this->current;
	}

	/**
	 * Get the request currently being dispatched.
	 */
	public function getCurrentRequest(): Request
	{
		return $this->currentRequest;
	}

	/**
	 * Get the list of registered routes.
	 */
	public function getRoutes(): array
	{
		return $this->routes;
	}

	/**
	 * Load the provided routes.
	 */
	public function loadRoutes(Closure|string $routes): void
	{
		if ($routes instanceof Closure) {
			$routes($this);
		} else {
			require $routes;
		}
	}

	/**
	 * Add a new PATCH route to the router.
	 */
	public function patch(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * Add a new POST route to the router.
	 */
	public function post(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * Create a response instance from the given value.
	 */
	public function prepareResponse(Request $request, mixed $response): Response
	{
		if (! $response instanceof Response) {
			$response = (new ResponseFactory)->make($response);
		}

		return $response->prepare();
	}

	/**
	 * Add a new PUT route to the router.
	 */
	public function put(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute('PUT', $uri, $action);
	}

	/**
	 * Run the given route and return the response.
	 */
	protected function runRoute(Request $request, Route $route): Response
	{
		$request->setRouteResolver(fn () => $route);

		return $this->prepareResponse($request, $route->run());
	}
}
