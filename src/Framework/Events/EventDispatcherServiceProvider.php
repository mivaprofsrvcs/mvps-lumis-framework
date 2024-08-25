<?php

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class EventDispatcherServiceProvider extends ServiceProvider
{
	/**
	 * Register the event dispatcher service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('events', function ($app) {
			return new Dispatcher($app);
		});
	}
}
