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

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Support\Str;

class RouteBinding
{
	/**
	 * Create a class based binding using the IoC container.
	 */
	protected static function createClassBinding(Container $container, string $binding): Closure
	{
		return function ($value, $route) use ($container, $binding) {
			// If the binding contains an '@' sign, it indicates that the class
			// name is being delimited from the bind method name. This allows
			// for bindings to execute multiple bind methods within a single
			// class for convenience.
			[$class, $method] = Str::parseCallback($binding, 'bind');

			$callable = [$container->make($class), $method];

			return $callable($value, $route);
		};
	}

	/**
	 * Create a Route model binding for a given callback.
	 */
	public static function forCallback(Container $container, Closure|string $binder): Closure
	{
		return is_string($binder)
			? static::createClassBinding($container, $binder)
			: $binder;
	}

	/**
	 * Create a Route model binding for a model.
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
	 */
	public static function forModel(Container $container, string $class, Closure|null $callback = null): Closure
	{
		return function ($value, $route) use ($container, $class, $callback) {
			if (is_null($value)) {
				return;
			}

			// Attempt to retrieve models using the first method on the model
			// instance.  If retrieval fails, throw a model not found exception;
			// otherwise, return the instance.
			$instance = $container->make($class);

			$routeBindingMethod = $route->allowsTrashedBindings() &&
				in_array(SoftDeletes::class, class_uses_recursive($instance))
					? 'resolveSoftDeletableRouteBinding'
					: 'resolveRouteBinding';

			if ($model = $instance->{$routeBindingMethod}($value)) {
				return $model;
			}

			// If a callback was provided, invoke it to determine the behavior
			// when the  model is not found. This offers flexibility in handling
			// missing models.
			if ($callback instanceof Closure) {
				return $callback($value);
			}

			throw (new ModelNotFoundException)->setModel($class);
		};
	}
}
