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

namespace MVPS\Lumis\Framework\Providers;

class AggregateServiceProvider extends ServiceProvider
{
	/**
	 * The provider class names.
	 *
	 * @var array
	 */
	protected array $providers = [];

	/**
	 * The list of the service provider instances.
	 *
	 * @var array
	 */
	protected array $instances = [];

	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		$provides = [];

		foreach ($this->providers as $provider) {
			$instance = $this->app->resolveProvider($provider);

			$provides = array_merge($provides, $instance->provides());
		}

		return $provides;
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->instances = [];

		foreach ($this->providers as $provider) {
			$this->instances[] = $this->app->register($provider);
		}
	}
}
