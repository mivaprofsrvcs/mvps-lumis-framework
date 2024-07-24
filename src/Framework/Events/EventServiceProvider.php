<?php

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * Register the event service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('events', function ($app) {
			return new Dispatcher($app);
		});
	}
}
