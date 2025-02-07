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
