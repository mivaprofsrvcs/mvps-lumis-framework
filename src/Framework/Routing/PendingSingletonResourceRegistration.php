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

use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Routing\Traits\CreatesRegularExpressionRouteConstraints;
use MVPS\Lumis\Framework\Support\Arr;

class PendingSingletonResourceRegistration
{
	use CreatesRegularExpressionRouteConstraints;
	use Macroable;

	/**
	 * The resource controller.
	 *
	 * @var string
	 */
	protected string $controller;

	/**
	 * The resource name.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The resource options.
	 *
	 * @var array
	 */
	protected array $options;

	/**
	 * The resource's registration status.
	 *
	 * @var bool
	 */
	protected bool $registered = false;

	/**
	 * The resource registrar.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\ResourceRegistrar
	 */
	protected ResourceRegistrar $registrar;

	/**
	 * Create a new pending singleton resource registration instance.
	 */
	public function __construct(ResourceRegistrar $registrar, string $name, string $controller, array $options)
	{
		$this->name = $name;
		$this->options = $options;
		$this->registrar = $registrar;
		$this->controller = $controller;
	}

	/**
	 * Indicate that the resource should have creation and storage routes.
	 */
	public function creatable(): static
	{
		$this->options['creatable'] = true;

		return $this;
	}

	/**
	 * Indicate that the resource should have a deletion route.
	 */
	public function destroyable(): static
	{
		$this->options['destroyable'] = true;

		return $this;
	}

	/**
	 * Set the methods the controller should exclude.
	 */
	public function except(mixed $methods): static
	{
		$this->options['except'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}

	/**
	 * Add middleware to the resource routes.
	 */
	public function middleware(mixed $middleware): static
	{
		$middleware = Arr::wrap($middleware);

		foreach ($middleware as $key => $value) {
			$middleware[$key] = (string) $value;
		}

		$this->options['middleware'] = $middleware;

		return $this;
	}

	/**
	 * Set the route name for a controller action.
	 */
	public function name(string $method, string $name): static
	{
		$this->options['names'][$method] = $name;

		return $this;
	}

	/**
	 * Set the route names for controller actions.
	 */
	public function names(array|string $names): static
	{
		$this->options['names'] = $names;

		return $this;
	}

	/**
	 * Set the methods the controller should apply to.
	 */
	public function only(mixed $methods): static
	{
		$this->options['only'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}

	/**
	 * Override a route parameter's name.
	 */
	public function parameter(string $previous, string $new): static
	{
		$this->options['parameters'][$previous] = $new;

		return $this;
	}

	/**
	 * Override the route parameter names.
	 */
	public function parameters(array|string $parameters): static
	{
		$this->options['parameters'] = $parameters;

		return $this;
	}

	/**
	 * Register the singleton resource route.
	 */
	public function register(): RouteCollection
	{
		$this->registered = true;

		return $this->registrar->singleton($this->name, $this->controller, $this->options);
	}

	/**
	 * Add "where" constraints to the resource routes.
	 */
	public function where(mixed $wheres): static
	{
		$this->options['wheres'] = $wheres;

		return $this;
	}

	/**
	 * Specify middleware that should be removed from the resource routes.
	 */
	public function withoutMiddleware(array|string $middleware): static
	{
		$this->options['excluded_middleware'] = array_merge(
			(array) ($this->options['excluded_middleware'] ?? []),
			Arr::wrap($middleware)
		);

		return $this;
	}

	/**
	 * Handle the object's destruction.
	 */
	public function __destruct()
	{
		if (! $this->registered) {
			$this->register();
		}
	}
}
