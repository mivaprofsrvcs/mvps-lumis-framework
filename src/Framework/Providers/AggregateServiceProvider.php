<?php

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
