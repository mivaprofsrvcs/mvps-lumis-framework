<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Support\Reflector;
use MVPS\Lumis\Framework\Support\Str;
use ReflectionFunction;
use ReflectionMethod;

class RouteSignatureParameters
{
	/**
	 * Extract the route action's signature parameters.
	 */
	public static function fromAction(array $action, array $conditions = []): array
	{
		$callback = RouteAction::containsSerializedClosure($action)
			? unserialize($action['uses'])->getClosure()
			: $action['uses'];

		$parameters = is_string($callback)
			? static::fromClassMethodString($callback)
			: (new ReflectionFunction($callback))->getParameters();

		return match (true) {
			! empty($conditions['subClass']) => array_filter(
				$parameters,
				fn ($p) => Reflector::isParameterSubclassOf($p, $conditions['subClass'])
			),
			! empty($conditions['backedEnum']) => array_filter(
				$parameters,
				fn ($p) => Reflector::isParameterBackedEnumWithStringBackingType($p)
			),
			default => $parameters,
		};
	}

	/**
	 * Get the parameters for the given class / method by string.
	 */
	protected static function fromClassMethodString(string $uses): array
	{
		[$class, $method] = Str::parseCallback($uses);

		if (! method_exists($class, $method) && Reflector::isCallable($class, $method)) {
			return [];
		}

		return (new ReflectionMethod($class, $method))->getParameters();
	}
}
