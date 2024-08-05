<?php

namespace MVPS\Lumis\Framework\Cookie;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->singleton('cookie', function ($app) {
			$config = $app->make('config')->get('session');

			return (new CookieJar)->setDefaultPathAndDomain(
				$config['path'],
				$config['domain'],
				$config['secure'],
				$config['same_site'] ?? null
			);
		});
	}
}
