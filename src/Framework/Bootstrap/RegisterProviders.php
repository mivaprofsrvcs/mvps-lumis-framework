<?php

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
