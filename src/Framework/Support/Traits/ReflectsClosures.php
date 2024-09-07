<?php

namespace MVPS\Lumis\Framework\Support\Traits;

use Closure;
use MVPS\Lumis\Framework\Support\Reflector;
use ReflectionFunction;
use RuntimeException;

trait ReflectsClosures
{
	/**
	 * Get the class names / types of the parameters of the given Closure.
	 *
	 * @throws \ReflectionException
	 */
	protected function closureParameterTypes(Closure $closure): array
	{
		$reflection = new ReflectionFunction($closure);

		return collection($reflection->getParameters())->mapWithKeys(function ($parameter) {
			if ($parameter->isVariadic()) {
				return [$parameter->getName() => null];
			}

			return [$parameter->getName() => Reflector::getParameterClassName($parameter)];
		})->all();
	}

	/**
	 * Get the class name of the first parameter of the given Closure.
	 *
	 * @throws \ReflectionException
	 * @throws \RuntimeException
	 */
	protected function firstClosureParameterType(Closure $closure): string
	{
		$types = array_values($this->closureParameterTypes($closure));

		if (! $types) {
			throw new RuntimeException('The given Closure has no parameters.');
		}

		if (is_null($types[0])) {
			throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
		}

		return $types[0];
	}

	/**
	 * Get the class names of the first parameter of the given Closure, including union types.
	 *
	 * @throws \ReflectionException
	 * @throws \RuntimeException
	 */
	protected function firstClosureParameterTypes(Closure $closure): array
	{
		$reflection = new ReflectionFunction($closure);

		$types = collection($reflection->getParameters())->mapWithKeys(function ($parameter) {
			if ($parameter->isVariadic()) {
				return [$parameter->getName() => null];
			}

			return [$parameter->getName() => Reflector::getParameterClassNames($parameter)];
		})->filter()->values()->all();

		if (empty($types)) {
			throw new RuntimeException('The given Closure has no parameters.');
		}

		if (isset($types[0]) && empty($types[0])) {
			throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
		}

		return $types[0];
	}
}
