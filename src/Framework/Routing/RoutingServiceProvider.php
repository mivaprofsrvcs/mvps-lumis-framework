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
use MVPS\Lumis\Framework\Contracts\Routing\CallableDispatcher as CallableDispatcherContract;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactoryContract;
use MVPS\Lumis\Framework\Providers\ServiceProvider;
use MVPS\Lumis\Framework\Routing\CallableDispatcher;
use MVPS\Lumis\Framework\Routing\ControllerDispatcher;
use MVPS\Lumis\Framework\Routing\ResponseFactory;

class RoutingServiceProvider extends ServiceProvider
{
	/**
	 * Register the routing service provider.
	 */
	public function register(): void
	{
		$this->registerRouter();
		$this->registerUrlGenerator();
		$this->registerRedirector();
		$this->registerResponseFactory();
		$this->registerCallableDispatcher();
		$this->registerControllerDispatcher();
	}

	/**
	 * Register the callable dispatcher.
	 */
	protected function registerCallableDispatcher(): void
	{
		$this->app->singleton(CallableDispatcherContract::class, function ($app) {
			return new CallableDispatcher($app);
		});
	}

	/**
	 * Register the controller dispatcher.
	 */
	protected function registerControllerDispatcher(): void
	{
		$this->app->singleton(ControllerDispatcherContract::class, function ($app) {
			return new ControllerDispatcher($app);
		});
	}

	/**
	 * Register the redirector service.
	 */
	protected function registerRedirector(): void
	{
		$this->app->singleton('redirect', function ($app) {
			$redirector = new Redirector($app['url']);

			// Inject the session instance into the redirector if available,
			// enabling the use of flash data for redirects (ie "with" methods).
			if (isset($app['session.store'])) {
				$redirector->setSession($app['session.store']);
			}

			return $redirector;
		});
	}

	/**
	 * Register the response factory implementation.
	 */
	protected function registerResponseFactory(): void
	{
		$this->app->singleton(ResponseFactoryContract::class, function ($app) {
			return new ResponseFactory($app[ViewFactoryContract::class], $app['redirect']);
		});
	}

	/**
	 * Register the router instance.
	 */
	protected function registerRouter(): void
	{
		$this->app->singleton('router', function ($app) {
			return new Router($app['events'], $app);
		});
	}

	/**
	 * Register the URL generator service.
	 */
	protected function registerUrlGenerator(): void
	{
		$this->app->singleton('url', function ($app) {
			$routes = $app['router']->getRoutes();

			// The URL generator needs the route collection that exists on the router.
			// Keep in mind this is an object, so we're passing by references here
			// and all the registered routes will be available to the generator.
			$app->instance('routes', $routes);

			return new UrlGenerator(
				$routes,
				$app->rebinding('request', $this->requestRebinder()),
				$app['config']['app.asset_url'] ?? ''
			);
		});

		$this->app->extend('url', function (UrlGeneratorContract $url, $app) {
			// TODO: Implement this with application key functionality
			// $url->setKeyResolver(function () {
			// 	$config = $this->app->make('config');

			// 	return [$config->get('app.key'), ...($config->get('app.previous_keys') ?? [])];
			// });

			// If the route collection is "rebound" (e.g., when the routes remain
			// cached for the application), we need to rebind the routes on the URL
			// generator instance to ensure it uses the latest version of the routes.
			$app->rebinding('routes', function ($app, $routes) {
				$app['url']->setRoutes($routes);
			});

			return $url;
		});
	}

	/**
	 * Get the URL generator request rebinder.
	 */
	protected function requestRebinder(): Closure
	{
		return fn ($app, $request) => $app['url']->setRequest($request);
	}
}
