<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\ResponseFactory;

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
	 * @var \MVPS\Lumis\Framework\Contracts\Routing\RouteCollection
	 */
	protected RouteCollection $routes;

	/**
	 * All of the verbs supported by the router.
	 *
	 * @var string[]
	 */
	public static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * Create a new Router instance.
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container ?: new Container;
		$this->routes = new RouteCollection;
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
	public function addRoute(array|string $methods, string $uri, array|callable|string|null $action): Route
	{
		if ($this->actionReferencesController($action)) {
			$action = $this->convertToControllerAction($action);
		}

		$route = $this->createRoute($methods, $uri, $action);

		return $this->routes->add($route);
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
	protected function createRoute(array|string $methods, string $uri, array|callable|string|null $action): Route
	{
		return (new Route($methods, $uri, $action))
			->setRouter($this)
			->setContainer($this->container);
	}

	/**
	 * Get the currently dispatched route instance.
	 */
	public function current(): Route|null
	{
		return $this->current;
	}

	/**
	 * Get the current route action.
	 */
	public function currentRouteAction(): string|null
	{
		return $this->current()?->getAction()['controller'] ?? null;
	}

	/**
	 * Get the current route name.
	 */
	public function currentRouteName(): string|null
	{
		return $this->current()?->getName();
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
	 */
	protected function findRoute(Request $request): Route
	{
		$route = $this->routes->match($request);

		$this->current = $route;

		$route->setContainer($this->container);

		$this->container->instance(Route::class, $route);

		return $route;
	}

	/**
	 * Add a new GET route to the router.
	 */
	public function get(string $uri, array|callable|string|null $action): Route
	{
		return $this->addRoute(['GET', 'HEAD'], $uri, $action);
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
	public function getRoutes(): RouteCollection
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
	 * Register a new route with the given verbs.
	 */
	public function match(array|string $methods, string $uri, array|string|callable|null $action = null): Route
	{
		return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
	}

	/**
	 * Register a new OPTIONS route with the router.
	 */
	public function options(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute('OPTIONS', $uri, $action);
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
	 * Route a resource to a controller.
	 */
	public function resource(string $name, string $controller, array $options = []): PendingResourceRegistration
	{
		$registrar = $this->container && $this->container->bound(ResourceRegistrar::class)
			? $this->container->make(ResourceRegistrar::class)
			: new ResourceRegistrar($this);

		return new PendingResourceRegistration($registrar, $name, $controller, $options);
	}

	/**
	 * Register an array of resource controllers.
	 */
	public function resources(array $resources, array $options = []): void
	{
		foreach ($resources as $name => $controller) {
			$this->resource($name, $controller, $options);
		}
	}

	/**
	 * Run the given route and return the response.
	 */
	protected function runRoute(Request $request, Route $route): Response
	{
		$request->setRouteResolver(fn () => $route);

		return $this->prepareResponse($request, $route->run());
	}

	/**
	 * Set the route collection instance.
	 */
	public function setRoutes(RouteCollection $routes): void
	{
		foreach ($routes as $route) {
			$route->setRouter($this)
				->setContainer($this->container);
		}

		$this->routes = $routes;

		$this->container->instance('routes', $this->routes);
	}
}
