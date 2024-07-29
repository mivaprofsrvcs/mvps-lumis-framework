<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use LogicException;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Matching\HostValidator;
use MVPS\Lumis\Framework\Routing\Matching\MethodValidator;
use MVPS\Lumis\Framework\Routing\Matching\SchemeValidator;
use MVPS\Lumis\Framework\Routing\Matching\UriValidator;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route as SymfonyRoute;

class Route
{
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
	 * The regular expression requirements.
	 *
	 * @var array
	 */
	public array $wheres = [];

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
	 * Mark this route as a fallback route.
	 */
	public function fallback(): static
	{
		$this->isFallback = true;

		return $this;
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
	 * Set a regular expression requirement on the route.
	 */
	public function where(array|string $name, string|null $expression = null): static
	{
		foreach ($this->parseWhere($name, $expression) as $name => $expression) {
			$this->wheres[$name] = $expression;
		}

		return $this;
	}
}
