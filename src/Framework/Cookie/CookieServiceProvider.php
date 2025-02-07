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
