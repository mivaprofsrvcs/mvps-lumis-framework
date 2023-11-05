<?php

namespace MVPS\Lumis\Framework\Routing;

use BadMethodCallException;

abstract class Controller
{
	/**
	 * Run an action on the controller.
	 */
	public function callAction(string $method, array $parameters): mixed
	{
		return $this->{$method}(...array_values($parameters));
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @throws BadMethodCallException
	 */
	public function __call(string $method, array $parameters): mixed
	{
		throw new BadMethodCallException(
			sprintf('Method %s::%s does not exist.', static::class, $method)
		);
	}
}
