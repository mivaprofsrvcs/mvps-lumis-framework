<?php

namespace MVPS\Lumis\Framework\Support;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

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
	 * Register the composer service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('composer', function ($app) {
			return new Composer($app['files'], $app->basePath());
		});
	}
}
