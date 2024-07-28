<?php

namespace MVPS\Lumis\Framework\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Routing\Traits\CreatesRegularExpressionRouteConstraints;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Reflector;

class RouteRegistrar
{
	use CreatesRegularExpressionRouteConstraints;

	/**
	 * The attributes that are aliased.
	 *
	 * @var array
	 */
	protected array $aliases = [
		'name' => 'as',
		'scopeBindings' => 'scope_bindings',
		'withoutMiddleware' => 'excluded_middleware',
	];

	/**
	 * The attributes that can be set through this class.
	 *
	 * @var array<string>
	 */
	protected array $allowedAttributes = [
		'as',
		'controller',
		'domain',
		'middleware',
		'missing',
		'name',
		'namespace',
		'prefix',
		'scopeBindings',
		'where',
		'withoutMiddleware',
	];

	/**
	 * The attributes to pass on to the router.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * The methods to dynamically pass through to the router.
	 *
	 * @var array<string>
	 */
	protected array $passthru = [
		'get',
		'post',
		'put',
		'patch',
		'delete',
		'options',
		'any',
	];

	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * Create a new route registrar instance.
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * Route an API resource to a controller.
	 */
	public function apiResource(string $name, string $controller, array $options = []): PendingResourceRegistration
	{
		return $this->router->apiResource($name, $controller, $this->attributes + $options);
	}

	/**
	 * Route an API singleton resource to a controller.
	 */
	public function apiSingleton(
		string $name,
		string $controller,
		array $options = []
	): PendingSingletonResourceRegistration {
		return $this->router->apiSingleton($name, $controller, $this->attributes + $options);
	}

	/**
	 * Set the value for a given attribute.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function attribute(string $key, mixed $value): self
	{
		if (! in_array($key, $this->allowedAttributes)) {
			throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
		}

		if ($key === 'middleware') {
			foreach ($value as $index => $middleware) {
				$value[$index] = (string) $middleware;
			}
		}

		$attributeKey = Arr::get($this->aliases, $key, $key);

		if ($key === 'withoutMiddleware') {
			$value = array_merge(
				(array) ($this->attributes[$attributeKey] ?? []),
				Arr::wrap($value)
			);
		}

		$this->attributes[$attributeKey] = $value;

		return $this;
	}

	/**
	 * Compile the action into an array including the attributes.
	 */
	protected function compileAction(Closure|array|string|null $action): array
	{
		if (is_null($action)) {
			return $this->attributes;
		}

		if (is_string($action) || $action instanceof Closure) {
			$action = ['uses' => $action];
		}

		if (is_array($action) && array_is_list($action) && Reflector::isCallable($action)) {
			if (strncmp($action[0], '\\', 1)) {
				$action[0] = '\\' . $action[0];
			}

			$action = [
				'uses' => $action[0] . '@' . $action[1],
				'controller' => $action[0] . '@' . $action[1],
			];
		}

		return array_merge($this->attributes, $action);
	}

	/**
	 * Register a new route with the given verbs.
	 */
	public function match(array|string $methods, string $uri, Closure|array|string|null $action = null): Route
	{
		return $this->router->match($methods, $uri, $this->compileAction($action));
	}

	/**
	 * Register a new route with the router.
	 */
	protected function registerRoute(string $method, string $uri, Closure|array|string|null $action = null): Route
	{
		if (! is_array($action)) {
			$action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
		}

		return $this->router->{$method}($uri, $this->compileAction($action));
	}

	/**
	 * Route a resource to a controller.
	 */
	public function resource(string $name, string $controller, array $options = []): PendingResourceRegistration
	{
		return $this->router->resource($name, $controller, $this->attributes + $options);
	}

	/**
	 * Route a singleton resource to a controller.
	 */
	public function singleton(
		string $name,
		string $controller,
		array $options = []
	): PendingSingletonResourceRegistration {
		return $this->router->singleton($name, $controller, $this->attributes + $options);
	}

	/**
	 * Dynamically handle calls into the route registrar.
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call(string $method, array $parameters): Route|static
	{
		if (in_array($method, $this->passthru)) {
			return $this->registerRoute($method, ...$parameters);
		}

		if (in_array($method, $this->allowedAttributes)) {
			if ($method === 'middleware') {
				return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
			}

			return $this->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
		}

		throw new BadMethodCallException(
			sprintf('Method %s::%s does not exist.', static::class, $method)
		);
	}
}
