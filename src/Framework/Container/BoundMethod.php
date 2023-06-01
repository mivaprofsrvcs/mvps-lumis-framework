<?php

namespace MVPS\Lumis\Framework\Container;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;
use ReflectionParameter;
use InvalidArgumentException;
use ReflectionFunctionAbstract;

class BoundMethod
{
	/**
	 * Get the dependency for the given call parameter.
	 *
	 * @throws BindingResolutionException
	 */
	protected static function addDependencyForCallParameter(
		Container $container,
		ReflectionParameter $parameter,
		array &$parameters,
		&$dependencies
	): void {
		$paramName = $parameter->getName();
		$className = Utilities::getParameterClassName($parameter);

		if (array_key_exists($paramName, $parameters)) {
			$dependencies[] = $parameters[$paramName];

			unset($parameters[$paramName]);
		} elseif (! is_null($className)) {
			if (array_key_exists($className, $parameters)) {
				$dependencies[] = $parameters[$className];

				unset($parameters[$className]);
			} elseif ($parameter->isVariadic()) {
				$variadicDependencies = $container->make($className);

				$dependencies = array_merge(
					$dependencies,
					is_array($variadicDependencies) ? $variadicDependencies : [$variadicDependencies]
				);
			} else {
				$dependencies[] = $container->make($className);
			}
		} elseif ($parameter->isDefaultValueAvailable()) {
			$dependencies[] = $parameter->getDefaultValue();
		} elseif (! $parameter->isOptional() && ! array_key_exists($paramName, $parameters)) {
			throw new BindingResolutionException(
				"Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}"
			);
		}
	}

	/**
	 * Call the given Closure or Class@method and inject its dependencies.
	 *
	 * @throws \ReflectionException
	 * @throws \InvalidArgumentException
	 */
	public static function call(
		Container $container,
		callable|string $callback,
		array $parameters = [],
		string|null $defaultMethod = null
	): mixed {
		if (is_string($callback) && ! $defaultMethod && method_exists($callback, '__invoke')) {
			$defaultMethod = '__invoke';
		}

		if (static::isCallableWithAtSign($callback) || $defaultMethod) {
			return static::callClass($container, $callback, $parameters, $defaultMethod);
		}

		return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
			return $callback(...array_values(static::getMethodDependencies($container, $callback, $parameters)));
		});
	}

	/**
	 * Call a method that has been bound to the container.
	 */
	protected static function callBoundMethod(Container $container, callable $callback, mixed $default): mixed
	{
		if (! is_array($callback)) {
			return Utilities::unwrapIfClosure($default);
		}

		// Here we need to turn the array callable into a Class@method string we can use to
		// examine the container and see if there are any method bindings for this given
		// method. If there are, we can call this method binding callback immediately.
		$method = static::normalizeMethod($callback);

		if ($container->hasMethodBinding($method)) {
			return $container->callMethodBinding($method, $callback[0]);
		}

		return Utilities::unwrapIfClosure($default);
	}

	/**
	 * Call a string reference to a class using Class@method syntax.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected static function callClass(
		Container $container,
		string $target,
		array $parameters = [],
		string|null $defaultMethod = null
	): mixed {
		$segments = explode('@', $target);
		$method = count($segments) === 2 ? $segments[1] : $defaultMethod;

		if (is_null($method)) {
			throw new InvalidArgumentException('Method not provided.');
		}

		return static::call($container, [$container->make($segments[0]), $method], $parameters);
	}

	/**
	 * Get the proper reflection instance for the given callback.
	 *
	 * @throws \ReflectionException
	 */
	protected static function getCallReflector(callable|string $callback): ReflectionFunctionAbstract
	{
		if (is_string($callback) && str_contains($callback, '::')) {
			$callback = explode('::', $callback);
		} elseif (is_object($callback) && ! $callback instanceof Closure) {
			$callback = [$callback, '__invoke'];
		}

		return is_array($callback)
			? new ReflectionMethod($callback[0], $callback[1])
			: new ReflectionFunction($callback);
	}

	/**
	 * Get all dependencies for a given method.
	 *
	 * @throws \ReflectionException
	 */
	protected static function getMethodDependencies(
		Container $container,
		callable|string $callback,
		array $parameters = []
	): array {
		$dependencies = [];

		foreach (static::getCallReflector($callback)->getParameters() as $parameter) {
			static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
		}

		return array_merge($dependencies, array_values($parameters));
	}

	/**
	 * Determine if the given string is in Class@method syntax.
	 */
	protected static function isCallableWithAtSign(mixed $callback): bool
	{
		return is_string($callback) && str_contains($callback, '@');
	}

	/**
	 * Normalize the given callback into a Class@method string.
	 */
	protected static function normalizeMethod(callable $callback): string
	{
		$class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

		return "{$class}@{$callback[1]}";
	}
}
