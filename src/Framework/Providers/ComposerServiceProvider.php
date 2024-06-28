<?php

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Support\Composer;
use MVPS\Lumis\Framework\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return ['composer'];
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('composer', function ($app) {
			return new Composer($app['files'], $app->basePath());
		});
	}
}
