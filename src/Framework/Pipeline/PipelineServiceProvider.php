<?php

namespace MVPS\Lumis\Framework\Pipeline;

use Illuminate\Contracts\Pipeline\Hub as PipelineHubContract;
use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return [
			PipelineHubContract::class,
			'pipeline',
		];
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->singleton(PipelineHubContract::class, Hub::class);

		$this->app->bind('pipeline', fn ($app) => new Pipeline($app));
	}
}
