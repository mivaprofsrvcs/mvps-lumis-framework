<?php

namespace MVPS\Lumis\Framework\Routing;

use ArrayObject;
use Closure;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Http\Responsable;
use MVPS\Lumis\Framework\Contracts\Routing\BindingRegistrar;
use MVPS\Lumis\Framework\Contracts\Routing\Registrar as RegistrarContract;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\Events\RouteMatched;
use MVPS\Lumis\Framework\Routing\Events\Routing;
use MVPS\Lumis\Framework\Routing\Middleware\SortedMiddleware;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Support\Stringable;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class Router implements BindingRegistrar, RegistrarContract
{
	use Macroable {
		__call as macroCall;
	}

	/**
	 * The registered route value binders.
	 *
	 * @var array
	 */
	protected array $binders = [];

	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
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
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected Dispatcher $events;

	/**
	 * The registered custom implicit binding callback.
	 *
	 * @var array
	 */
	protected array $implicitBindingCallback = [];

	/**
	 * The priority-sorted list of middleware.
	 *
	 * Forces the listed middleware to always be in the given order.
	 *
	 * @var array
	 */
	public array $middlewarePriority = [];

	/**
	 * The globally available parameter patterns.
	 *
	 * @var array
	 */
	protected array $patterns = [];

	/**
	 * The list of registered routes.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Routing\RouteCollection
	 */
	protected RouteCollection $routes;

	/**
	 * All of the verbs supported by the router.
	 *
	 * @var array<string>
	 */
	public static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * Create a new Router instance.
	 */
	public function __construct(Dispatcher $events, Container $container = null)
	{
		$this->events = $events;
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
		return $this->routes->add($this->createRoute($methods, $uri, $action));
	}

	/**
	 * Add the necessary where clauses to the route based on its initial registration.
	 */
	protected function addWhereClausesToRoute(Route $route): Route
	{
		$route->where(array_merge(
			$this->patterns,
			$route->getAction()['where'] ?? []
		));

		return $route;
	}

	/**
	 * Register a new route responding to all verbs.
	 */
	public function any(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute(self::$verbs, $uri, $action);
	}

	/**
	 * Route an API resource to a controller.
	 */
	public function apiResource(string $name, string $controller, array $options = []): PendingResourceRegistration
	{
		$only = [
			'index',
			'show',
			'store',
			'update',
			'destroy',
		];

		if (isset($options['except'])) {
			$only = array_diff($only, (array) $options['except']);
		}

		return $this->resource(
			$name,
			$controller,
			array_merge(['only' => $only], $options)
		);
	}

	/**
	 * Register an array of API resource controllers.
	 */
	public function apiResources(array $resources, array $options = []): void
	{
		foreach ($resources as $name => $controller) {
			$this->apiResource($name, $controller, $options);
		}
	}

	/**
	 * Route an API singleton resource to a controller.
	 */
	public function apiSingleton(
		string $name,
		string $controller,
		array $options = []
	): PendingSingletonResourceRegistration {
		$only = [
			'store',
			'show',
			'update',
			'destroy',
		];

		if (isset($options['except'])) {
			$only = array_diff($only, (array) $options['except']);
		}

		return $this->singleton(
			$name,
			$controller,
			array_merge(['only' => $only], $options)
		);
	}

	/**
	 * Register an array of API singleton resource controllers.
	 */
	public function apiSingletons(array $singletons, array $options = []): void
	{
		foreach ($singletons as $name => $controller) {
			$this->apiSingleton($name, $controller, $options);
		}
	}

	/**
	 * Add a new route parameter binder.
	 */
	public function bind(string $key, string|callable $binder): void
	{
		$this->binders[str_replace('-', '_', $key)] = RouteBinding::forCallback($this->container, $binder);
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
		// If the route targets a controller, parse the action into an array
		// format suitable for registration.  Create a Closure to handle the
		// controller call and register the route.
		if ($this->actionReferencesController($action)) {
			$action = $this->convertToControllerAction($action);
		}

		$route = $this->newRoute($methods, $uri, $action);

		$this->addWhereClausesToRoute($route);

		return $route;
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
	 * Determine if the current route matches a pattern.
	 */
	public function currentRouteNamed(mixed ...$patterns): bool
	{
		return (bool) $this->current()?->named(...$patterns);
	}

	/**
	 * Determine if the current route action matches a given action.
	 */
	public function currentRouteUses(string $action): bool
	{
		return $this->currentRouteAction() === $action;
	}

	/**
	 * Add a new DELETE route to the router.
	 */
	public function delete(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Dispatch the request to a route and return the response.
	 */
	public function dispatch(Request $request): Response
	{
		$this->currentRequest = $request;

		return $this->dispatchToRoute($request);
	}

	/**
	 * Dispatch the request to a route and return the response.
	 */
	public function dispatchToRoute(Request $request): Response
	{
		return $this->runRoute($request, $this->findRoute($request));
	}

	/**
	 * Register a new fallback route with the router.
	 */
	public function fallback(array|callable|string|null $action): Route
	{
		$placeholder = 'fallbackPlaceholder';

		return $this->addRoute('GET', "{{$placeholder}}", $action)
			->where($placeholder, '.*')
			->fallback();
	}

	/**
	 * Find the route matching a given request.
	 */
	protected function findRoute(Request $request): Route
	{
		$this->events->dispatch(new Routing($request));

		$route = $this->routes->match($request);

		$this->current = $route;

		$route->setContainer($this->container);

		$this->container->instance(Route::class, $route);

		return $route;
	}

	/**
	 * Add a new GET route to the router.
	 */
	public function get(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute(['GET', 'HEAD'], $uri, $action);
	}

	/**
	 * Get the binding callback for a given binding.
	 */
	public function getBindingCallback(string $key): Closure|null
	{
		return $this->binders[str_replace('-', '_', $key)] ?? null;
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
	 * Get the currently dispatched route instance.
	 */
	public function getCurrentRoute(): Route|null
	{
		return $this->current();
	}

	/**
	 * Get the global "where" patterns.
	 */
	public function getPatterns(): array
	{
		return $this->patterns;
	}

	/**
	 * Get the list of registered routes.
	 */
	public function getRoutes(): RouteCollection
	{
		return $this->routes;
	}

	/**
	 * Check if a route with the given name exists.
	 */
	public function has(string|array $name): bool
	{
		$names = is_array($name) ? $name : func_get_args();

		foreach ($names as $value) {
			if (! $this->routes->hasNamedRoute($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a route parameter for the current route.
	 */
	public function input(string $key, string|null $default = null): mixed
	{
		return $this->current()?->parameter($key, $default);
	}

	/**
	 * Alias for the "currentRouteNamed" method.
	 */
	public function is(mixed ...$patterns): bool
	{
		return $this->currentRouteNamed(...$patterns);
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
	 * Register a model binder for a wildcard.
	 */
	public function model(string $key, string $class, Closure|null $callback = null): void
	{
		$this->bind($key, RouteBinding::forModel($this->container, $class, $callback));
	}

	/**
	 * Create a new Route object.
	 */
	public function newRoute(array|string $methods, string $uri, mixed $action): Route
	{
		return (new Route($methods, $uri, $action))
			->setRouter($this)
			->setContainer($this->container);
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
	public function patch(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * Set a global where pattern on all routes.
	 */
	public function pattern(string $key, string $pattern): void
	{
		$this->patterns[$key] = $pattern;
	}

	/**
	 * Set a group of global where patterns on all routes.
	 */
	public function patterns(array $patterns): void
	{
		foreach ($patterns as $key => $pattern) {
			$this->pattern($key, $pattern);
		}
	}

	/**
	 * Call the binding callback for the given key.
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
	 */
	protected function performBinding(string $key, string $value, Route $route): mixed
	{
		return call_user_func($this->binders[$key], $value, $route);
	}

	/**
	 * Add a new POST route to the router.
	 */
	public function post(string $uri, array|callable|string|null $action = null): Route
	{
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * Create a response instance from the given value.
	 */
	public function prepareResponse(Request $request, mixed $response): Response
	{
		return static::toResponse($request, $response);
	}

	/**
	 * Add a new PUT route to the router.
	 */
	public function put(string $uri, array|callable|string|null $action = null): Route
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
	 * Set the global resource parameter mapping.
	 */
	public function resourceParameters(array $parameters = []): void
	{
		ResourceRegistrar::setParameters($parameters);
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
	 * Get or set the verbs used in the resource URIs.
	 */
	public function resourceVerbs(array $verbs = []): array|null
	{
		return ResourceRegistrar::verbs($verbs);
	}

	/**
	 * Return the response returned by the given route.
	 */
	public function respondWithRoute(string $name): Response
	{
		$route = tap($this->routes->getByName($name))->bind($this->currentRequest);

		return $this->runRoute($this->currentRequest, $route);
	}

	/**
	 * Run the given route and return the response.
	 */
	protected function runRoute(Request $request, Route $route): Response
	{
		$request->setRouteResolver(fn () => $route);

		$this->events->dispatch(new RouteMatched($route, $request));

		return $this->prepareResponse($request, $route->run());
	}

	/**
	 * Set the container instance used by the router.
	 */
	public function setContainer(Container $container): static
	{
		$this->container = $container;

		return $this;
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

	/**
	 * Route a singleton resource to a controller.
	 */
	public function singleton(
		string $name,
		string $controller,
		array $options = []
	): PendingSingletonResourceRegistration {
		$registrar = $this->container && $this->container->bound(ResourceRegistrar::class)
			? $this->container->make(ResourceRegistrar::class)
			: new ResourceRegistrar($this);

		return new PendingSingletonResourceRegistration($registrar, $name, $controller, $options);
	}

	/**
	 * Register an array of singleton resource controllers.
	 */
	public function singletons(array $singletons, array $options = []): void
	{
		foreach ($singletons as $name => $controller) {
			$this->singleton($name, $controller, $options);
		}
	}

	/**
	 * Set the unmapped global resource parameters to singular.
	 */
	public function singularResourceParameters(bool $singular = true): void
	{
		ResourceRegistrar::singularParameters($singular);
	}

	/**
	 * Sort the given middleware by priority.
	 */
	protected function sortMiddleware(Collection $middlewares): array
	{
		return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
	}

	/**
	 * Substitute the route bindings onto the route.
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException
	 */
	public function substituteBindings(Route $route): Route
	{
		foreach ($route->parameters() as $key => $value) {
			if (isset($this->binders[$key])) {
				$route->setParameter($key, $this->performBinding($key, $value, $route));
			}
		}

		return $route;
	}

	/**
	 * Substitute the implicit route bindings for the given route.
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException
	 */
	public function substituteImplicitBindings(Route $route): mixed
	{
		$default = fn () => ImplicitRouteBinding::resolveForRoute($this->container, $route);

		return call_user_func($this->implicitBindingCallback ?? $default, $this->container, $route, $default);
	}

	/**
	 * Register a callback to run after implicit bindings are substituted.
	 */
	public function substituteImplicitBindingsUsing(callable $callback): static
	{
		$this->implicitBindingCallback = $callback;

		return $this;
	}

	/**
	 * Converts a variety of response data into a standardized Response object.
	 *
	 * This method takes a PSR-7 compliant Request object and a mixed value
	 * representing the response data. It attempts to convert the data into a
	 * Response object suitable for returning from a controller method.
	 *
	 * Static version of "prepareResponse" method.
	 */
	public static function toResponse(Request $request, mixed $response): Response
	{
		if ($response instanceof Responsable) {
			$response = $response->toResponse($request);
		} elseif (! $response instanceof Response) {
			$content = $response;
			$status = 200;
			$contentType = 'text/html';

			if ($response instanceof Stringable) {
				$content = $response->__toString();
			} elseif (
				$response instanceof Arrayable ||
				$response instanceof Jsonable ||
				$response instanceof ArrayObject ||
				$response instanceof JsonSerializable ||
				$response instanceof stdClass ||
				is_array($response)
			) {
				$contentType = 'application/json';
			} elseif ($response instanceof ResponseInterface) {
				$content = (string) $response->getBody();
				$status = $response->getStatusCode();

				if ($response->hasHeader('Content-Type')) {
					$contentType = $response->getHeaderLine('Content-Type');
				}
			}

			$response = response($content, $status, ['Content-Type' => $contentType]);
		}

		// TODO: Implement not modified response
		// if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
		// 	$response->setNotModified();
		// }

		return $response->prepare();
	}

	/**
	 * Remove any duplicate middleware from the given array.
	 */
	public static function uniqueMiddleware(array $middleware): array
	{
		$seen = [];
		$result = [];

		foreach ($middleware as $value) {
			$key = is_object($value) ? spl_object_id($value) : $value;

			if (! isset($seen[$key])) {
				$seen[$key] = true;
				$result[] = $value;
			}
		}

		return $result;
	}

	/**
	 * Alias for the "currentRouteUses" method.
	 */
	public function uses(array ...$patterns): bool
	{
		foreach ($patterns as $pattern) {
			if (Str::is($pattern, $this->currentRouteAction())) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register a new route that returns a view.
	 */
	public function view(
		string $uri,
		string $view,
		array $data = [],
		int|array $status = 200,
		array $headers = []
	): Route {
		return $this->match(['GET', 'HEAD'], $uri, '\MVPS\Lumis\Framework\Routing\ViewController')
			->setDefaults([
				'view' => $view,
				'data' => $data,
				'status' => is_array($status) ? 200 : $status,
				'headers' => is_array($status) ? $status : $headers,
			]);
	}

	/**
	 * Dynamically handle calls into the router instance.
	 */
	public function __call(string $method, array $parameters): mixed
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		if ($method === 'middleware') {
			return (new RouteRegistrar($this))
				->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
		}

		if ($method !== 'where' && Str::startsWith($method, 'where')) {
			return (new RouteRegistrar($this))->{$method}(...$parameters);
		}

		return (new RouteRegistrar($this))
			->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
	}
}
