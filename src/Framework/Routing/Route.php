<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use LogicException;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Support\Str;

class Route
{
	/**
	 * The route action array.
	 *
	 * @var array
	 */
	public array $action;

	/**
	 * The compiled version of the route.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\CompiledRoute|null
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
	 * The HTTP method the route relates to.
	 *
	 * @var string
	 */
	public string $method;

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
	protected string $uri;

	/**
	 * Create a new Route instance,
	 */
	public function __construct(string $method, string $uri, Closure|array|string|null $action)
	{
		$this->method = $method;
		$this->uri = $this->formatUri($uri);
		$this->action = $this->parseAction($action);
	}

	/**
	 * Bind the route to a given request for execution.
	 */
	public function bind(Request $request): static
	{
		$this->compileRoute();

		$this->parameters = (new RouteParameterBinder($this))->getParameters($request);

		$this->originalParameters = $this->parameters;

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
	 * Compile the route into a CompiledRoute instance.
	 */
	protected function compileRoute(): CompiledRoute
	{
		if (is_null($this->compiled)) {
			$this->compiled = (new CompiledRoute($this->getUri()))->compile();
		}

		return $this->compiled;
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
	 * Get the dispatcher for the route's controller.
	 */
	public function getControllerDispatcher(): ControllerDispatcherContract
	{
		if ($this->container->bound(ControllerDispatcherContract::class)) {
			return $this->container->make(ControllerDispatcherContract::class);
		}

		return new ControllerDispatcher($this->container);
	}

	/**
	 * Get the controller method used for the route.
	 */
	protected function getControllerMethod(): string
	{
		return $this->parseControllerCallback()[1];
	}

	/**
	 * Get the parameter names for the route.
	 */
	public function getParameterNames(): array
	{
		if (is_null($this->parameterNames)) {
			$this->parameterNames = $this->compileParameterNames();
		}

		return $this->parameterNames;
	}

	/**
	 * Get the key / value list of original parameters for the route.
	 *
	 * @throws \LogicException
	 */
	public function getOriginalParameters(): array
	{
		if (! isset($this->originalParameters)) {
			throw new LogicException('Route is not bound.');
		}

		return $this->originalParameters;
	}

	/**
	 * Get the key / value list of parameters for the route.
	 *
	 * @throws \LogicException
	 */
	public function getParameters(): array
	{
		if (! isset($this->parameters)) {
			throw new LogicException('Route is not bound.');
		}

		return $this->parameters;
	}

	/**
	 * Get the key / value list of parameters without null values.
	 */
	public function getParametersWithoutNulls(): array
	{
		return array_filter($this->getParameters(), fn ($param) => ! is_null($param));
	}

	/**
	 * Get the URI associated with the route.
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * Checks whether the route's action is a controller.
	 */
	protected function isControllerAction(): bool
	{
		return is_string($this->action['uses'] ?? null);
	}

	/**
	 * Determine if the route matches a given request.
	 */
	public function matches(Request $request): bool
	{
		$this->compileRoute();

		$path = rtrim($request->getUri()->getPath(), '/') ?: '/';

		return preg_match($this->compiled->getRegex(), rawurldecode($path));
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

		return $this->container->make(CallableDispatcher::class)
			->dispatch($this, $callable);
	}

	/**
	 * Run the route action and return the response.
	 */
	protected function runController(): mixed
	{
		return $this->getControllerDispatcher()
			->dispatch($this, $this->getController(), $this->getControllerMethod());
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
	 * Set the router instance on the route.
	 */
	public function setRouter(Router $router): static
	{
		$this->router = $router;

		return $this;
	}
}
