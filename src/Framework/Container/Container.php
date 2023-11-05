<?php

namespace MVPS\Lumis\Framework\Container;

use Closure;
use Exception;
use TypeError;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionException;
use ReflectionParameter;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
	/**
	 * The current globally available container (if any).
	 *
	 * @var static
	 */
	protected static self $instance;

	/**
	 * The registered aliases keyed by the abstract name.
	 *
	 * @var array[]
	 */
	protected array $abstractAliases = [];

	/**
	 * The registered type aliases.
	 *
	 * @var string[]
	 */
	protected array $aliases = [];

	/**
	 * The container's bindings.
	 *
	 * @var array[]
	 */
	protected array $bindings = [];

	/**
	 * The stack of concretions currently being built.
	 *
	 * @var array[]
	 */
	protected array $buildStack = [];

	/**
	 * The contextual binding map.
	 *
	 * @var array[]
	 */
	public array $contextual = [];

	/**
	 * The container's shared instances.
	 *
	 * @var object[]
	 */
	protected array $instances = [];

	/**
	 * The container's method bindings.
	 *
	 * @var \Closure[]
	 */
	protected array $methodBindings = [];

	/**
	 * All of the registered rebound callbacks.
	 *
	 * @var array[]
	 */
	protected array $reboundCallbacks = [];

	/**
	 * An array of the types that have been resolved.
	 *
	 * @var bool[]
	 */
	protected array $resolved = [];

	/**
	 * The parameter override stack.
	 *
	 * @var array[]
	 */
	protected array $with = [];

	/**
	 * Alias a type to a different name.
	 *
	 * @throws \LogicException
	 */
	public function alias(string $abstract, string $alias): void
	{
		if ($alias === $abstract) {
			throw new LogicException("[{$abstract}] is aliased to itself.");
		}

		$this->aliases[$alias] = $abstract;
		$this->abstractAliases[$abstract][] = $alias;
	}

	/**
	 * Register a binding with the container.
	 *
	 * @throws \TypeError
	 */
	public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
	{
		$this->dropStaleInstances($abstract);

		if (is_null($concrete)) {
			$concrete = $abstract;
		}

		// If the factory is not a Closure, it means it is a class name which is
		// bound into this container to the abstract type and we will wrap it
		// inside its own Closure to be more convenient when extending.
		if (! $concrete instanceof Closure) {
			if (! is_string($concrete)) {
				throw new TypeError(
					static::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null'
				);
			}

			$concrete = $this->getClosure($abstract, $concrete);
		}

		$this->bindings[$abstract] = compact('concrete', 'shared');

		if ($this->resolved($abstract)) {
			$this->rebound($abstract);
		}
	}

	/**
	 * Determine if the given abstract type has been bound.
	 */
	public function bound(string $abstract): bool
	{
		return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || $this->isAlias($abstract);
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @throws BindingResolutionException
	 * @throws CircularDependencyException
	 */
	public function build(Closure|string $concrete): mixed
	{
		// If the concrete type is actually a Closure, execute it and hand back
		// the results of the functions, which allows functions to be used as
		// resolvers for more fine-tuned resolution of these objects.
		if ($concrete instanceof Closure) {
			return $concrete($this, $this->getLastParameterOverride());
		}

		try {
			$reflector = new ReflectionClass($concrete);
		} catch (ReflectionException $e) {
			throw new BindingResolutionException('Target class [' . $concrete . '] does not exist.', 0, $e);
		}

		// If the type is not instantiable, there is no binding registered for
		// the abstractions so we need to bail out.
		if (! $reflector->isInstantiable()) {
			return $this->notInstantiable($concrete);
		}

		$this->buildStack[] = $concrete;

		$constructor = $reflector->getConstructor();

		// If there are no constructors, that means there are no dependencies and
		// we can resolve the instances of the objects right away.
		if (is_null($constructor)) {
			array_pop($this->buildStack);

			return new $concrete;
		}

		$dependencies = $constructor->getParameters();

		// Once we have all the constructor's parameters we can create each of the
		// dependency instances and then use the reflection instances to make a
		// new instance of this class, injecting the created dependencies in.
		try {
			$instances = $this->resolveDependencies($dependencies);
		} catch (BindingResolutionException $e) {
			array_pop($this->buildStack);

			throw $e;
		}

		array_pop($this->buildStack);

		return $reflector->newInstanceArgs($instances);
	}

	/**
	 * Call the given Closure or Class@method and inject its dependencies.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function call(callable|string $callback, array $parameters = [], string|null $defaultMethod = null): mixed
	{
		$pushedToBuildStack = false;
		$className = $this->getClassForCallable($callback);

		if ($className && ! in_array($className, $this->buildStack, true)) {
			$this->buildStack[] = $className;

			$pushedToBuildStack = true;
		}

		$result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

		if ($pushedToBuildStack) {
			array_pop($this->buildStack);
		}

		return $result;
	}

	/**
	 * Call the method binding for the given method.
	 */
	public function callMethodBinding(string $method, mixed $instance): mixed
	{
		return call_user_func($this->methodBindings[$method], $instance, $this);
	}

	/**
	 * Drop all of the stale instances and aliases.
	 */
	protected function dropStaleInstances(string $abstract): void
	{
		unset($this->instances[$abstract], $this->aliases[$abstract]);
	}

	/**
	 * Find the concrete binding for the given abstract in the contextual binding array.
	 */
	protected function findInContextualBindings(string|callable $abstract): Closure|string|null
	{
		return $this->contextual[end($this->buildStack)][$abstract] ?? null;
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 */
	public function flush(): void
	{
		$this->abstractAliases = [];
		$this->aliases = [];
		$this->bindings = [];
		$this->instances = [];
		$this->resolved = [];
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @throws CircularDependencyException  No entry was found for **this** identifier.
	 * @throws NotFoundException  Error while retrieving the entry.
	 */
	public function get(string $id): mixed
	{
		try {
			return $this->resolve($id);
		} catch (Exception $e) {
			if ($this->has($id) || $e instanceof CircularDependencyException) {
				throw $e;
			}

			throw new NotFoundException($id, is_int($e->getCode()) ? $e->getCode() : 0, $e);
		}
	}

	/**
	 * Get the alias for an abstract if available.
	 */
	public function getAlias(string $abstract): string
	{
		return isset($this->aliases[$abstract]) ? $this->getAlias($this->aliases[$abstract]) : $abstract;
	}

	/**
	 * Get the class name for the given callback, if one can be determined.
	 */
	protected function getClassForCallable(callable|string $callback): string|false
	{
		if (PHP_VERSION_ID >= 80200) {
			$reflector = new ReflectionFunction($callback(...));

			if (is_callable($callback) && ! $reflector->isAnonymous()) {
				return $reflector->getClosureScopeClass()->name ?? false;
			}

			return false;
		}

		if (! is_array($callback)) {
			return false;
		}

		return is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
	}

	/**
	 * Get the Closure to be used when building a type.
	 */
	protected function getClosure(string $abstract, string $concrete): Closure
	{
		return function ($container, $parameters = []) use ($abstract, $concrete) {
			if ($abstract == $concrete) {
				return $container->build($concrete);
			}

			return $container->resolve($concrete, $parameters);
		};
	}

	/**
	 * Get the concrete type for a given abstract.
	 */
	protected function getConcrete(string|callable $abstract): mixed
	{
		if (isset($this->bindings[$abstract])) {
			return $this->bindings[$abstract]['concrete'];
		}

		return $abstract;
	}

	/**
	 * Get the contextual concrete binding for the given abstract.
	 */
	protected function getContextualConcrete(string|callable $abstract): Closure|string|array|null
	{
		if (! is_null($binding = $this->findInContextualBindings($abstract))) {
			return $binding;
		}

		// Check if a contextual binding might be bound under an alias of the
		// given abstract type.
		if (empty($this->abstractAliases[$abstract])) {
			return null;
		}

		foreach ($this->abstractAliases[$abstract] as $alias) {
			$binding = $this->findInContextualBindings($alias);

			if (! is_null($binding)) {
				return $binding;
			}
		}

		return null;
	}

	/**
	 * Get the globally available instance of the container.
	 */
	public static function getInstance(): static
	{
		if (is_null(static::$instance)) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Get the last parameter override.
	 */
	protected function getLastParameterOverride(): array
	{
		return count($this->with) ? end($this->with) : [];
	}

	/**
	 * Get a parameter override for a dependency.
	 */
	protected function getParameterOverride(ReflectionParameter $dependency): mixed
	{
		return $this->getLastParameterOverride()[$dependency->name];
	}

	/**
	 * Get the rebound callbacks for a given type.
	 */
	protected function getReboundCallbacks(string $abstract): array
	{
		return $this->reboundCallbacks[$abstract] ?? [];
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 */
	public function has(string $id): bool
	{
		return $this->bound($id);
	}

	/**
	 * Determine if the container has a method binding.
	 */
	public function hasMethodBinding(string $method): bool
	{
		return isset($this->methodBindings[$method]);
	}

	/**
	 * Determine if the given dependency has a parameter override.
	 */
	protected function hasParameterOverride(ReflectionParameter $dependency): bool
	{
		return array_key_exists($dependency->name, $this->getLastParameterOverride());
	}

	/**
	 * Register an existing instance as shared in the container.
	 */
	public function instance(string $abstract, mixed $instance): mixed
	{
		$this->removeAbstractAlias($abstract);

		$isBound = $this->bound($abstract);

		unset($this->aliases[$abstract]);

		$this->instances[$abstract] = $instance;

		if ($isBound) {
			$this->rebound($abstract);
		}

		return $instance;
	}

	/**
	 * Determine if a given string is an alias.
	 */
	public function isAlias(string $name): bool
	{
		return isset($this->aliases[$name]);
	}

	/**
	 * Determine if the given concrete is buildable.
	 */
	protected function isBuildable(mixed $concrete, string $abstract): bool
	{
		return $concrete === $abstract || $concrete instanceof Closure;
	}

	/**
	 * Determine if a given type is shared.
	 */
	public function isShared(string $abstract): bool
	{
		return isset($this->instances[$abstract])
			|| (
				isset($this->bindings[$abstract]['shared'])
				&& $this->bindings[$abstract]['shared'] === true
			);
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @throws BindingResolutionException
	 */
	public function make(string|callable $abstract, array $parameters = []): mixed
	{
		return $this->resolve($abstract, $parameters);
	}

	/**
	 * Throw an exception that the concrete is not instantiable.
	 *
	 * @throws BindingResolutionException
	 */
	protected function notInstantiable(string $concrete): void
	{
		$message = "Target [$concrete] is not instantiable";

		if (! empty($this->buildStack)) {
			$previous = implode(', ', $this->buildStack);

			$message .= " while building [$previous].";
		}

		throw new BindingResolutionException($message . '.');
	}

	/**
	 * Fire the "rebound" callbacks for the given abstract type.
	 */
	protected function rebound(string $abstract): void
	{
		$instance = $this->make($abstract);

		foreach ($this->getReboundCallbacks($abstract) as $callback) {
			$callback($this, $instance);
		}
	}

	/**
	 * Remove an alias from the contextual binding alias cache.
	 */
	protected function removeAbstractAlias(string $searched): void
	{
		if (! isset($this->aliases[$searched])) {
			return;
		}

		foreach ($this->abstractAliases as $abstract => $aliases) {
			foreach ($aliases as $index => $alias) {
				if ($alias === $searched) {
					unset($this->abstractAliases[$abstract][$index]);
				}
			}
		}
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @throws BindingResolutionException
	 * @throws CircularDependencyException
	 */
	protected function resolve(string|callable $abstract, array $parameters = []): mixed
	{
		$abstract = $this->getAlias($abstract);
		$concrete = $this->getContextualConcrete($abstract);

		$needsContextualBuild = ! empty($parameters) || ! is_null($concrete);

		// If an instance of the type is currently being managed as a singleton
		// return an existing instance instead of instantiating new instances.
		if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
			return $this->instances[$abstract];
		}

		$this->with[] = $parameters;

		if (is_null($concrete)) {
			$concrete = $this->getConcrete($abstract);
		}

		$object = $this->isBuildable($concrete, $abstract) ? $this->build($concrete) : $this->make($concrete);

		// If the requested type is registered as a singleton we'll want to cache off
		// the instances in "memory" so we can return it later without creating an
		// entirely new instance of an object on each subsequent request for it.
		if ($this->isShared($abstract) && ! $needsContextualBuild) {
			$this->instances[$abstract] = $object;
		}

		// Before returning, we will also set the resolved flag to "true" and pop off
		// the parameter overrides for this build. After those two things are done
		// we will be ready to return back the fully constructed class instance.
		$this->resolved[$abstract] = true;

		array_pop($this->with);

		return $object;
	}

	/**
	 * Determine if the given abstract type has been resolved.
	 */
	public function resolved(string $abstract): bool
	{
		if ($this->isAlias($abstract)) {
			$abstract = $this->getAlias($abstract);
		}

		return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
	}

	/**
	 * Resolve a class based dependency from the container.
	 *
	 * @throws BindingResolutionException
	 */
	protected function resolveClass(ReflectionParameter $parameter): mixed
	{
		try {
			return $parameter->isVariadic()
				? $this->resolveVariadicClass($parameter)
				: $this->make(Utilities::getParameterClassName($parameter));
		} catch (BindingResolutionException $e) {
			if ($parameter->isDefaultValueAvailable()) {
				array_pop($this->with);

				return $parameter->getDefaultValue();
			}

			if ($parameter->isVariadic()) {
				array_pop($this->with);

				return [];
			}

			throw $e;
		}
	}

	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 *
	 * @throws BindingResolutionException
	 */
	protected function resolveDependencies(array $dependencies): array
	{
		$results = [];

		foreach ($dependencies as $dependency) {
			if ($this->hasParameterOverride($dependency)) {
				$results[] = $this->getParameterOverride($dependency);

				continue;
			}

			$result = is_null(Utilities::getParameterClassName($dependency))
				? $this->resolvePrimitive($dependency)
				: $this->resolveClass($dependency);

			if ($dependency->isVariadic()) {
				$results = array_merge($results, $result);
			} else {
				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * Resolve a non-class hinted primitive dependency.
	 *
	 * @throws BindingResolutionException
	 */
	protected function resolvePrimitive(ReflectionParameter $parameter): mixed
	{
		$concrete = $this->getContextualConcrete('$' . $parameter->getName());

		if (! is_null($concrete)) {
			return Utilities::unwrapIfClosure($concrete, $this);
		}

		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}

		if ($parameter->isVariadic()) {
			return [];
		}

		throw new BindingResolutionException(
			"Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}"
		);
	}

	/**
	 * Resolve a class based variadic dependency from the container.
	 */
	protected function resolveVariadicClass(ReflectionParameter $parameter): mixed
	{
		$className = Utilities::getParameterClassName($parameter);

		$abstract = $this->getAlias($className);

		if (! is_array($concrete = $this->getContextualConcrete($abstract))) {
			return $this->make($className);
		}

		return array_map(fn ($abstract) => $this->resolve($abstract), $concrete);
	}

	/**
	 * Set the shared instance of the container.
	 */
	public static function setInstance(self|null $container = null): self|static
	{
		return static::$instance = $container;
	}

	/**
	 * Register a shared binding in the container.
	 */
	public function singleton(string $abstract, Closure|string|null $concrete = null): void
	{
		$this->bind($abstract, $concrete, true);
	}
}
