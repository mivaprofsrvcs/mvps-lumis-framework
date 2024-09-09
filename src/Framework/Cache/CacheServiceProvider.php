<?php

namespace MVPS\Lumis\Framework\Cache;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return [
			'cache',
			'cache.store',
			RateLimiter::class,
		];
	}

	/**
	 * Register the cache service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('cache', function ($app) {
			return new CacheManager($app);
		});

		$this->app->singleton('cache.store', function ($app) {
			return $app['cache']->driver();
		});

		$this->app->singleton(RateLimiter::class, function ($app) {
			return new RateLimiter(
				$app->make('cache')->driver($app['config']->get('cache.limiter'))
			);
		});
	}
}
