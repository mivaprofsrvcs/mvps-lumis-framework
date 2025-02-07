<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Routing;

use Illuminate\Support\Reflector;
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
