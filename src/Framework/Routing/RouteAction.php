<?php

namespace MVPS\Lumis\Framework\Routing;

use LogicException;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Reflector;
use UnexpectedValueException;

class RouteAction
{
	/**
	 * Find the callable in an action array.
	 */
	protected static function findCallable(array $action): callable
	{
		return Arr::first($action, function ($value, $key) {
			return Reflector::isCallable($value) && is_numeric($key);
		});
	}

	/**
	 * Make an action for an invokable controller.
	 */
	protected static function makeInvokable(string $action): string
	{
		if (! method_exists($action, '__invoke')) {
			throw new UnexpectedValueException("Invalid route action: [{$action}].");
		}

		return $action . '@__invoke';
	}

	/**
	 * Get an action for a route that has no action.
	 *
	 * @throws \LogicException
	 */
	protected static function missingAction(string $uri): array
	{
		return ['uses' => function () use ($uri) {
			throw new LogicException('Route for "' . $uri . '" has no action.');
		}];
	}

	/**
	 * Parse the given action into an array.
	 */
	public static function parse(string $uri, mixed $action): array
	{
		if (is_null($action)) {
			return static::missingAction($uri);
		}

		if (Reflector::isCallable($action, true)) {
			return ! is_array($action)
				? ['uses' => $action]
				: [
					'uses' => $action[0] . '@' . $action[1],
					'controller' => $action[0] . '@' . $action[1],
				];
		} elseif (! isset($action['uses'])) {
			$action['uses'] = static::findCallable($action);
		}

		if (is_string($action['uses']) && ! str_contains($action['uses'], '@')) {
			$action['uses'] = static::makeInvokable($action['uses']);
		}

		return $action;
	}
}
