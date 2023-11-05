<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Contracts\Routing\CallableDispatcher as CallableDispatcherContract;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher as ControllerDispatcherContract;
use MVPS\Lumis\Framework\Routing\CallableDispatcher;
use MVPS\Lumis\Framework\Routing\ControllerDispatcher;
use MVPS\Lumis\Framework\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->registerRouter();
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
	 * Register the router instance.
	 */
	protected function registerRouter(): void
	{
		$this->app->singleton('router', function ($app) {
			return new Router($app);
		});
	}
}
