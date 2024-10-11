<?php

namespace MVPS\Lumis\Framework\Log;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
	/**
	 * Register the log service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('log', fn ($app) => new LogManager($app));
	}
}
