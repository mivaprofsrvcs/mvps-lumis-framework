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

namespace MVPS\Lumis\Framework\Bootstrap;

use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;
use MVPS\Lumis\Framework\Contracts\Framework\Application;

class RegisterProviders implements Bootstrapper
{
	/**
	 * The service providers that should be merged before registration.
	 *
	 * @var array
	 */
	protected static array $merge = [];

	/**
	 * The path to the bootstrap provider configuration file.
	 *
	 * @var string|null
	 */
	protected static string|null $bootstrapProviderPath;

	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$this->mergeAdditionalProviders($app);

		$app->registerConfiguredProviders();
	}

	/**
	 * Flush the bootstrapper's global state.
	 */
	public static function flushState(): void
	{
		static::$bootstrapProviderPath = null;

		static::$merge = [];
	}

	/**
	 * Merge the given providers into the provider configuration before registration.
	 */
	public static function merge(array $providers, string|null $bootstrapProviderPath = null): void
	{
		static::$bootstrapProviderPath = $bootstrapProviderPath;

		static::$merge = array_values(array_filter(array_unique(
			array_merge(static::$merge, $providers)
		)));
	}

	/**
	 * Merge the additional configured providers into the configuration.
	 */
	protected function mergeAdditionalProviders(Application $app): void
	{
		if (static::$bootstrapProviderPath && file_exists(static::$bootstrapProviderPath)) {
			$packageProviders = require static::$bootstrapProviderPath;

			foreach ($packageProviders as $index => $provider) {
				if (! class_exists($provider)) {
					unset($packageProviders[$index]);
				}
			}
		}

		$app->make('config')
			->set(
				'app.providers',
				array_merge(
					$app->make('config')->get('app.providers') ?? [],
					static::$merge,
					array_values($packageProviders ?? []),
				),
			);
	}
}
