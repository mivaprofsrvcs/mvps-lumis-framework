<?php

namespace MVPS\Lumis\Framework\Routing\Traits;

use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Reflector;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use stdClass;

trait ResolvesRouteDependencies
{
	/**
	 * Determine if an object of the given class is in a list of parameters.
	 */
	protected function alreadyInParameters(string $class, array $parameters): bool
	{
		return ! is_null(Arr::first($parameters, fn ($value) => $value instanceof $class));
	}

	/**
	 * Resolve the object method's type-hinted dependencies.
	 */
	protected function resolveClassMethodDependencies(array $parameters, object $instance, string $method): array
	{
		if (! method_exists($instance, $method)) {
			return $parameters;
		}

		return $this->resolveMethodDependencies($parameters, new ReflectionMethod($instance, $method));
	}

	/**
	 * Resolve the given method's type-hinted dependencies.
	 */
	public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector): array
	{
		$instanceCount = 0;
		$skippableValue = new stdClass;
		$values = array_values($parameters);

		foreach ($reflector->getParameters() as $key => $parameter) {
			$instance = $this->transformDependency($parameter, $parameters, $skippableValue);

			if ($instance !== $skippableValue) {
				$instanceCount++;

				$this->spliceIntoParameters($parameters, $key, $instance);
			} elseif (! isset($values[$key - $instanceCount]) && $parameter->isDefaultValueAvailable()) {
				$this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
			}
		}

		return $parameters;
	}

	/**
	 * Splice the given value into the parameter list.
	 */
	protected function spliceIntoParameters(array &$parameters, string $offset, mixed $value): void
	{
		array_splice($parameters, $offset, 0, [$value]);
	}

	/**
	 * Transform the given parameter into a class instance.
	 */
	protected function transformDependency(
		ReflectionParameter $parameter,
		array $parameters,
		object $skippableValue
	): mixed {
		$className = Reflector::getParameterClassName($parameter);

		if ($className && ! $this->alreadyInParameters($className, $parameters)) {
			$isEnum = (new ReflectionClass($className))->isEnum();

			return $parameter->isDefaultValueAvailable()
				? ($isEnum ? $parameter->getDefaultValue() : null)
				: $this->container->make($className);
		}

		return $skippableValue;
	}
}
