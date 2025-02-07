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
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * The global callback that should be used to load the application's routes.
	 *
	 * @var \Closure|null
	 */
	protected static Closure|null $alwaysLoadRoutesUsing;

	/**
	 * The callback that should be used to load the application's routes.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $loadRoutesUsing = null;

	/**
	 * The controller namespace for the application.
	 *
	 * @var string|null
	 */
	protected string|null $namespace = null;

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
	}

	/**
	 * Load the application routes.
	 */
	protected function loadRoutes(): void
	{
		if (! is_null(self::$alwaysLoadRoutesUsing)) {
			$this->app->call(self::$alwaysLoadRoutesUsing);
		}

		if (! is_null($this->loadRoutesUsing)) {
			$this->app->call($this->loadRoutesUsing);
		} elseif (method_exists($this, 'map')) {
			$this->app->call([$this, 'map']);
		}
	}

	/**
	 * Register the callback that will be used to load the application's routes.
	 */
	public static function loadRoutesUsing(Closure|null $routesCallback): void
	{
		self::$alwaysLoadRoutesUsing = $routesCallback;
	}

	/**
	 * Register application routes service.
	 */
	public function register(): void
	{
		$this->booted(function () {
			$this->setRootControllerNamespace();

			$this->loadRoutes();

			$this->app->booted(function () {
				$this->app['router']
					->getRoutes()
					->refreshNameLookups();

				$this->app['router']
					->getRoutes()
					->refreshActionLookups();
			});
		});
	}

	/**
	 * Register the callback that will be used to load the application's routes.
	 */
	protected function routes(Closure $routesCallback): static
	{
		$this->loadRoutesUsing = $routesCallback;

		return $this;
	}

	/**
	 * Set the root controller namespace for the application.
	 */
	protected function setRootControllerNamespace(): void
	{
		if (! is_null($this->namespace)) {
			$this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
		}
	}
}
