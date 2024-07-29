<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use LogicException;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Contracts\Routing\Controllers\HasMiddleware;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Controllers\Middleware;
use MVPS\Lumis\Framework\Routing\Matching\HostValidator;
use MVPS\Lumis\Framework\Routing\Matching\MethodValidator;
use MVPS\Lumis\Framework\Routing\Matching\SchemeValidator;
use MVPS\Lumis\Framework\Routing\Matching\UriValidator;
use MVPS\Lumis\Framework\Routing\Traits\CreatesRegularExpressionRouteConstraints;
use MVPS\Lumis\Framework\Routing\Traits\FiltersControllerMiddleware;
use MVPS\Lumis\Framework\Routing\Traits\ResolvesRouteDependencies;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route as SymfonyRoute;

class Route
{
	use CreatesRegularExpressionRouteConstraints;
	use FiltersControllerMiddleware;
	use ResolvesRouteDependencies;

	/**
	 * The route action array.
	 *
	 * @var array
	 */
	public array $action;

	/**
	 * The fields that implicit binding should use for a given parameter.
	 *
	 * @var array
	 */
	protected array $bindingFields = [];

	/**
	 * The compiled version of the route.
	 *
	 * @var \Symfony\Component\Routing\CompiledRoute|null
	 */
	public CompiledRoute|null $compiled = null;

	/**
	 * The computed gathered middleware.
	 *
	 * @var array|null
	 */
	public array|null $computedMiddleware = null;

	/**
	 * The container instance used by the route.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container|null
	 */
	protected Container|null $container = null;

	/**
	 * The controller instance.
	 *
	 * @var mixed
	 */
	public mixed $controller = null;

	/**
	 * The default values for the route.
	 *
	 * @var array
	 */
	public array $defaults = [];

	/**
	 * Indicates whether the route is a fallback route.
	 *
	 * @var bool
	 */
	public bool $isFallback = false;

	/**
	 * Indicates the maximum number of seconds the route should acquire a
	 * session lock for.
	 *
	 * @var int|null
	 */
	protected int|null $lockSeconds = null;

	/**
	 * The HTTP methods the route responds to.
	 *
	 * @var array
	 */
	public array $methods;

	/**
	 * The array of matched parameters' original values.
	 *
	 * @var array|null
	 */
	public array|null $originalParameters = null;

	/**
	 * The array of matched parameters.
	 *
	 * @var array|null
	 */
	public array|null $parameters = null;

	/**
	 * The parameter names for the route.
	 *
	 * @var array|null
	 */
	public array|null $parameterNames = null;

	/**
	 * The router instance used by the route.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router|null
	 */
	protected Router|null $router = null;

	/**
	 * The URI pattern the route relates to.
	 *
	 * @var string
	 */
	public string $uri;

	/**
	 * The validators used by the routes.
	 *
	 * @var array
	 */
	public static array $validators = [];

	/**
	 * Indicates the maximum number of seconds the route should wait while
	 * attempting to acquire a session lock.
	 *
	 * @var int|null
	 */
	protected int|null $waitSeconds = null;

	/**
	 * The regular expression requirements.
	 *
	 * @var array
	 */
	public array $wheres = [];

	/**
	 * Indicates "trashed" models can be retrieved when resolving implicit
	 * model bindings for this route.
	 *
	 * @var bool
	 */
	protected bool $withTrashedBindings = false;

	/**
	 * Create a new Route instance,
	 */
	public function __construct(array|string $methods, string $uri, Closure|array|string|null $action)
	{
		$this->methods = (array) $methods;
		$this->uri = $this->formatUri($uri);
		$this->action = $this->parseAction($action);

		if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
			$this->methods[] = 'HEAD';
		}
	}

	/**
	 * Determines if the route allows "trashed" models to be retrieved when
	 * resolving implicit model bindings.
	 */
	public function allowsTrashedBindings(): bool
	{
		return $this->withTrashedBindings;
	}

	/**
	 * Bind the route to a given request for execution.
	 */
	public function bind(Request $request): static
	{
		$this->compileRoute();

		$this->parameters = (new RouteParameterBinder($this))->parameters($request);

		$this->originalParameters = $this->parameters;

		return $this;
	}

	/**
	 * Get the binding field for the given parameter.
	 */
	public function bindingFieldFor(string|int $parameter): string|null
	{
		$fields = is_int($parameter) ? array_values($this->bindingFields) : $this->bindingFields;

		return $fields[$parameter] ?? null;
	}

	/**
	 * Get the binding fields for the route.
	 */
	public function bindingFields(): array
	{
		return $this->bindingFields ?? [];
	}

	/**
	 * Specify that the route should not allow concurrent requests from the same session.
	 */
	public function block(int|null $lockSeconds = 10, int|null $waitSeconds = 10): static
	{
		$this->lockSeconds = $lockSeconds;
		$this->waitSeconds = $waitSeconds;

		return $this;
	}

	/**
	 * Compile the parameter names for the route.
	 */
	protected function compileParameterNames(): array
	{
		preg_match_all('/\{(.*?)\}/', $this->uri, $matches);

		return array_map(fn ($match) => trim($match, '?'), $matches[1]);
	}

	/**
	 * Compile the route into a Symfony CompiledRoute instance.
	 */
	protected function compileRoute(): CompiledRoute
	{
		if (! $this->compiled) {
			$this->compiled = $this->toSymfonyRoute()->compile();
		}

		return $this->compiled;
	}

	/**
	 * Get the dispatcher for the route's controller.
	 */
	public function controllerDispatcher(): ControllerDispatcherContract
	{
		if ($this->container->bound(ControllerDispatcherContract::class)) {
			return $this->container->make(ControllerDispatcherContract::class);
		}

		return new ControllerDispatcher($this->container);
	}

	/**
	 * Get the middleware for the route's controller.
	 */
	public function controllerMiddleware(): array
	{
		if (! $this->isControllerAction()) {
			return [];
		}

		[$controllerClass, $controllerMethod] = [
			$this->getControllerClass(),
			$this->getControllerMethod(),
		];

		if (is_a($controllerClass, HasMiddleware::class, true)) {
			return $this->staticallyProvidedControllerMiddleware($controllerClass, $controllerMethod);
		}

		if (method_exists($controllerClass, 'getMiddleware')) {
			return $this->controllerDispatcher()
				->getMiddleware($this->getController(), $controllerMethod);
		}

		return [];
	}

	/**
	 * Set a default value for the route.
	 */
	public function defaults(string $key, mixed $value): static
	{
		$this->defaults[$key] = $value;

		return $this;
	}

	/**
	 * Get or set the domain for the route.
	 */
	public function domain(string|null $domain = null): static|string|null
	{
		if (is_null($domain)) {
			return $this->getDomain();
		}

		$parsed = RouteUri::parse($domain);

		$this->action['domain'] = $parsed->uri;

		$this->bindingFields = array_merge($this->bindingFields, $parsed->bindingFields);

		return $this;
	}

	/**
	 * Determine if the route should enforce scoping of multiple implicit
	 * model bindings.
	 */
	public function enforcesScopedBindings(): bool
	{
		return (bool) ($this->action['scope_bindings'] ?? false);
	}

	/**
	 * Get the middleware that should be removed from the route.
	 */
	public function excludedMiddleware(): array
	{
		return (array) ($this->action['excluded_middleware'] ?? []);
	}

	/**
	 * Mark this route as a fallback route.
	 */
	public function fallback(): static
	{
		$this->isFallback = true;

		return $this;
	}

	/**
	 * Flush the cached container instance on the route.
	 */
	public function flushController(): void
	{
		$this->computedMiddleware = null;
		$this->controller = null;
	}

	/**
	 * Get a formatted URI value.
	 */
	protected function formatUri(string $uri): string
	{
		if ($uri === '/') {
			return $uri;
		}

		return trim(trim($uri), '/');
	}

	/**
	 * Get all middleware, including the ones from the controller.
	 */
	public function gatherMiddleware(): array
	{
		if (! is_null($this->computedMiddleware)) {
			return $this->computedMiddleware;
		}

		$this->computedMiddleware = [];

		return $this->computedMiddleware = Router::uniqueMiddleware(array_merge(
			$this->middleware(),
			$this->controllerMiddleware()
		));
	}

	/**
	 * Get the action array or one of its properties for the route.
	 */
	public function getAction(string|null $key = null): mixed
	{
		return Arr::get($this->action, $key);
	}

	/**
	 * Get the method name of the route action.
	 */
	public function getActionMethod(): string
	{
		return Arr::last(explode('@', $this->getActionName()));
	}

	/**
	 * Get the action name for the route.
	 */
	public function getActionName(): string
	{
		return $this->action['controller'] ?? 'Closure';
	}

	/**
	 * Get the compiled version of the route.
	 */
	public function getCompiled(): CompiledRoute|null
	{
		return $this->compiled;
	}

	/**
	 * Get the controller instance for the route.
	 */
	public function getController(): mixed
	{
		if (! $this->controller) {
			$class = $this->getControllerClass();

			$this->controller = $this->container->make(ltrim($class, '\\'));
		}

		return $this->controller;
	}

	/**
	 * Get the controller class used for the route.
	 */
	public function getControllerClass(): string|null
	{
		return $this->isControllerAction() ? $this->parseControllerCallback()[0] : null;
	}

	/**
	 * Get the controller method used for the route.
	 */
	protected function getControllerMethod(): string
	{
		return $this->parseControllerCallback()[1];
	}

	/**
	 * Get the domain defined for the route.
	 */
	public function getDomain(): string|null
	{
		return isset($this->action['domain'])
			? str_replace(['http://', 'https://'], '', $this->action['domain'])
			: null;
	}

	/**
	 * Get the value of the action that should be taken on a missing model
	 * exception.
	 */
	public function getMissing(): Closure|null
	{
		$missing = $this->action['missing'] ?? null;

		return is_string($missing) &&
			Str::startsWith($missing, [
				'O:47:"Laravel\\SerializableClosure\\SerializableClosure',
				'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure',
			]) ? unserialize($missing) : $missing;
	}

	/**
	 * Get the name of the route instance.
	 */
	public function getName(): string|null
	{
		return $this->action['as'] ?? null;
	}

	/**
	 * Get the optional parameter names for the route.
	 */
	protected function getOptionalParameterNames(): array
	{
		preg_match_all('/\{(\w+?)\?\}/', $this->uri(), $matches);

		return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
	}

	/**
	 * Get the route validators for the instance.
	 */
	public static function getValidators(): array
	{
		if (! empty(static::$validators)) {
			return static::$validators;
		}

		// We use a chain of responsibility pattern with validator implementations
		// to match the route. Each validator checks if a part of the route passes
		// its validation. If all validators pass, the route matches the request.
		return static::$validators = [
			new UriValidator,
			new MethodValidator,
			new SchemeValidator,
			new HostValidator,
		];
	}

	/**
	 * Checks whether the route's action is a controller.
	 */
	protected function isControllerAction(): bool
	{
		return is_string($this->action['uses'] ?? null);
	}

	/**
	 * Determine if the route only responds to HTTP requests.
	 */
	public function isHttpOnly(): bool
	{
		return in_array('http', $this->action, true);
	}

	/**
	 * Determine if the route only responds to HTTPS requests.
	 */
	public function isHttpsOnly(): bool
	{
		return $this->isSecure();
	}

	/**
	 * Determine if the route only responds to HTTPS requests.
	 */
	public function isSecure(): bool
	{
		return in_array('https', $this->action, true);
	}

	/**
	 * Get the maximum number of seconds the route's session lock should be
	 * held for.
	 */
	public function locksFor(): int|null
	{
		return $this->lockSeconds;
	}

	/**
	 * Determine if the route matches a given request.
	 */
	public function matches(Request $request, bool $includingMethod = true): bool
	{
		$this->compileRoute();

		foreach (self::getValidators() as $validator) {
			if (! $includingMethod && $validator instanceof MethodValidator) {
				continue;
			}

			if (! $validator->matches($this, $request)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the HTTP verbs the route responds to.
	 */
	public function methods(): array
	{
		return $this->methods;
	}

	/**
	 * Get or set the middlewares attached to the route.
	 */
	public function middleware(array|string|null $middleware = null): static|array
	{
		if (is_null($middleware)) {
			return (array) ($this->action['middleware'] ?? []);
		}

		if (! is_array($middleware)) {
			$middleware = func_get_args();
		}

		foreach ($middleware as $index => $value) {
			$middleware[$index] = (string) $value;
		}

		$this->action['middleware'] = array_merge(
			(array) ($this->action['middleware'] ?? []),
			$middleware
		);

		return $this;
	}

	/**
	 * Add or change the route name.
	 */
	public function name(string $name): static
	{
		$this->action['as'] = isset($this->action['as']) ? $this->action['as'] . $name : $name;

		return $this;
	}

	/**
	 * Determine whether the route's name matches the given patterns.
	 */
	public function named(mixed ...$patterns): bool
	{
		$routeName = $this->getName();

		if (is_null($routeName)) {
			return false;
		}

		foreach ($patterns as $pattern) {
			if (Str::is($pattern, $routeName)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the key / value list of original parameters for the route.
	 *
	 * @throws \LogicException
	 */
	public function originalParameters(): array
	{
		if (! isset($this->originalParameters)) {
			throw new LogicException('Route is not bound.');
		}

		return $this->originalParameters;
	}

	/**
	 * Get a given parameter from the route.
	 */
	public function parameter(string $name, string|object|null $default = null): string|object|null
	{
		return Arr::get($this->parameters(), $name, $default);
	}

	/**
	 * Get the parameter names for the route.
	 */
	public function parameterNames(): array
	{
		if (is_null($this->parameterNames)) {
			$this->parameterNames = $this->compileParameterNames();
		}

		return $this->parameterNames;
	}

	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @throws \LogicException
	 */
	public function parameters(): array
	{
		if (! isset($this->parameters)) {
			throw new LogicException('Route is not bound.');
		}

		return $this->parameters;
	}

	/**
	 * Get the key / value list of parameters without null values.
	 */
	public function parametersWithoutNulls(): array
	{
		return array_filter($this->parameters(), fn ($param) => ! is_null($param));
	}

	/**
	 * Get the parent parameter of the given parameter.
	 */
	public function parentOfParameter(string $parameter): string|null
	{
		$key = array_search($parameter, array_keys($this->parameters));

		if ($key === 0 || $key === false) {
			return null;
		}

		return array_values($this->parameters)[$key - 1];
	}

	/**
	 * Parse the route action into a standard array.
	 */
	protected function parseAction(callable|array|string|null $action): array
	{
		return RouteAction::parse($this->uri, $action);
	}

	/**
	 * Parse the controller.
	 */
	protected function parseControllerCallback(): array
	{
		return Str::parseCallback($this->action['uses']);
	}

	/**
	 * Parse arguments to the where method into an array.
	 */
	protected function parseWhere(array|string $name, string $expression): array
	{
		return is_array($name) ? $name : [$name => $expression];
	}

	/**
	 * Determine if the route should prevent scoping of multiple implicit
	 * model bindings.
	 */
	public function preventsScopedBindings(): bool
	{
		return isset($this->action['scope_bindings']) &&
			$this->action['scope_bindings'] === false;
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException
	 */
	public function run(): mixed
	{
		$this->container = $this->container ?: new Container;

		try {
			if ($this->isControllerAction()) {
				return $this->runController();
			}

			return $this->runCallable();
		} catch (HttpResponseException $exception) {
			return $exception->getMessage();
		}
	}

	/**
	 * Run the route action and return the response.
	 */
	protected function runCallable(): mixed
	{
		$callable = $this->action['uses'];

		return $this->container[CallableDispatcher::class]
			->dispatch($this, $callable);
	}

	/**
	 * Run the route action and return the response.
	 */
	protected function runController(): mixed
	{
		return $this->controllerDispatcher()
			->dispatch($this, $this->getController(), $this->getControllerMethod());
	}

	/**
	 * Indicate that the route should enforce scoping of multiple implicit
	 * model bindings.
	 */
	public function scopeBindings(): static
	{
		$this->action['scope_bindings'] = true;

		return $this;
	}

	/**
	 * Set the action array for the route.
	 */
	public function setAction(array $action): static
	{
		$this->action = $action;

		if (isset($this->action['domain'])) {
			$this->domain($this->action['domain']);
		}

		return $this;
	}

	/**
	 * Set the binding fields for the route.
	 */
	public function setBindingFields(array $bindingFields): static
	{
		$this->bindingFields = $bindingFields;

		return $this;
	}

	/**
	 * Set the container instance on the route.
	 */
	public function setContainer(Container $container): static
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Set the default values for the route.
	 */
	public function setDefaults(array $defaults): static
	{
		$this->defaults = $defaults;

		return $this;
	}

	/**
	 * Set the fallback value.
	 */
	public function setFallback(bool $isFallback): static
	{
		$this->isFallback = $isFallback;

		return $this;
	}

	/**
	 * Set a parameter to the given value.
	 */
	public function setParameter(string $name, string|object|null $value = null): void
	{
		$this->parameters();

		$this->parameters[$name] = $value;
	}

	/**
	 * Set the router instance on the route.
	 */
	public function setRouter(Router $router): static
	{
		$this->router = $router;

		return $this;
	}

	/**
	 * Set a list of regular expression requirements on the route.
	 */
	public function setWheres(array $wheres): static
	{
		foreach ($wheres as $name => $expression) {
			$this->where($name, $expression);
		}

		return $this;
	}

	/**
	 * Get the parameters that are listed in the route / controller signature.
	 */
	public function signatureParameters(array $conditions = []): array
	{
		if (is_string($conditions)) {
			$conditions = ['subClass' => $conditions];
		}

		return RouteSignatureParameters::fromAction($this->action, $conditions);
	}

	/**
	 * Get the statically provided controller middleware for the given class and method.
	 */
	protected function staticallyProvidedControllerMiddleware(string $class, string $method): array
	{
		return collection($class::middleware())
			->map(function ($middleware) {
				return $middleware instanceof Middleware
					? $middleware
					: new Middleware($middleware);
			})
			->reject(function ($middleware) use ($method) {
				return static::methodExcludedByOptions(
					$method,
					['only' => $middleware->only, 'except' => $middleware->except]
				);
			})
			->map
			->middleware
			->flatten()
			->values()
			->all();
	}

	/**
	 * Convert the route to a Symfony route.
	 */
	public function toSymfonyRoute(): SymfonyRoute
	{
		return new SymfonyRoute(
			preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->uri()),
			$this->getOptionalParameterNames(),
			$this->wheres,
			['utf8' => true],
			$this->getDomain() ?: '',
			[],
			$this->methods
		);
	}

	/**
	 * Get the URI associated with the route.
	 */
	public function uri(): string
	{
		return $this->uri;
	}

	/**
	 * Get the maximum number of seconds to wait while attempting to acquire
	 * a session lock.
	 */
	public function waitsFor(): int|null
	{
		return $this->waitSeconds;
	}

	/**
	 * Set a regular expression requirement on the route.
	 */
	public function where(array|string $name, string $expression = ''): static
	{
		foreach ($this->parseWhere($name, $expression) as $name => $expression) {
			$this->wheres[$name] = $expression;
		}

		return $this;
	}

	/**
	 * Specify that the route should allow concurrent requests from the same session.
	 */
	public function withoutBlocking(): static
	{
		return $this->block(null, null);
	}

	/**
	 * Indicate that the route should not enforce scoping of multiple implicit
	 * model bindings.
	 */
	public function withoutScopedBindings(): static
	{
		$this->action['scope_bindings'] = false;

		return $this;
	}

	/**
	 * Allow "trashed" models to be retrieved when resolving implicit model
	 * bindings for this route.
	 */
	public function withTrashed(bool $withTrashed = true): static
	{
		$this->withTrashedBindings = $withTrashed;

		return $this;
	}
}
