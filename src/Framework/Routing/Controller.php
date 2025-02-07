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

use BadMethodCallException;
use Closure;

abstract class Controller
{
	/**
	 * The middleware registered on the controller.
	 *
	 * @var array
	 */
	protected array $middleware = [];

	/**
	 * Run an action on the controller.
	 */
	public function callAction(string $method, array $parameters): mixed
	{
		return $this->{$method}(...array_values($parameters));
	}

	/**
	 * Get the middleware assigned to the controller.
	 */
	public function getMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * Register middleware on the controller.
	 */
	public function middleware(Closure|array|string $middleware, array $options = []): ControllerMiddlewareOptions
	{
		foreach ((array) $middleware as $m) {
			$this->middleware[] = [
				'middleware' => $m,
				'options' => &$options,
			];
		}

		return new ControllerMiddlewareOptions($options);
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
