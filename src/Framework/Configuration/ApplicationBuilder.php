<?php

namespace MVPS\Lumis\Framework\Configuration;

use Closure;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Console\Kernel as ConsoleKernel;
use MVPS\Lumis\Framework\Contracts\Console\Kernel as ConsoleKernelContract;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as HttpKernelContract;
use MVPS\Lumis\Framework\Http\Kernel as HttpKernel;
use MVPS\Lumis\Framework\Routing\RouteServiceProvider as AppRouteServiceProvider;

class ApplicationBuilder
{
	/**
	 * The service provider that are marked for registration.
	 *
	 * @var array
	 */
	protected array $pendingProviders = [];

	public function __construct(protected Application $app)
	{
	}

	/**
	 * Register a callback to be invoked when the application is "booted".
	 */
	public function booted(callable $callback): static
	{
		$this->app->booted($callback);

		return $this;
	}

	/**
	 * Register a callback to be invoked when the application is "booting".
	 */
	public function booting(callable $callback): static
	{
		$this->app->booting($callback);

		return $this;
	}

	/**
	 * Create the routing callback for the application.
	 */
	protected function buildRoutingCallback(array|string|null $web, callable|null $then): Closure
	{
		return function () use ($web, $then) {
			if (is_string($web) || is_array($web)) {
				if (is_array($web)) {
					foreach ($web as $webRoute) {
						if (realpath($webRoute) !== false) {
							$this->app->make('router')
								->loadRoutes($webRoute);
						}
					}
				} else {
					$this->app->make('router')
						->loadRoutes($web);
				}
			}

			if (is_callable($then)) {
				$then($this->app);
			}
		};
	}

	/**
	 * Get the application instance.
	 */
	public function create(): Application
	{
		return $this->app;
	}

	/**
	 * Register a callback to be invoked when the application's service providers are registered.
	 */
	public function registered(callable $callback): static
	{
		$this->app->registered($callback);

		return $this;
	}

	/**
	 * Register an array of container bindings to be bound when the application is booting.
	 */
	public function withBindings(array $bindings): static
	{
		return $this->registered(function ($app) use ($bindings) {
			foreach ($bindings as $abstract => $concrete) {
				$app->bind($abstract, $concrete);
			}
		});
	}

	/**
	 * Register the standard kernel classes for the application.
	 */
	public function withKernels(): static
	{
		$this->app->singleton(
			HttpKernelContract::class,
			HttpKernel::class,
		);

		$this->app->singleton(
			ConsoleKernelContract::class,
			ConsoleKernel::class,
		);

		return $this;
	}

	/**
	 * Register additional service providers.
	 */
	public function withProviders(array $providers = [], bool $withBootstrapProviders = true): static
	{
		RegisterProviders::merge(
			$providers,
			$withBootstrapProviders ? $this->app->getBootstrapProvidersPath() : null
		);

		return $this;
	}

	/**
	 * Register the routing services for the application.
	 */
	public function withRouting(
		Closure|null $using = null,
		array|string|null $web = null,
		callable|null $then = null
	): static {
		if (is_null($using) && (is_string($web) || is_array($web) || is_callable($then))) {
			$using = $this->buildRoutingCallback($web, $then);
		}

		AppRouteServiceProvider::loadRoutesUsing($using);

		$this->app->booting(function () {
			$this->app->register(AppRouteServiceProvider::class, force: true);
		});

		return $this;
	}

	/**
	 * Register an array of singleton container bindings to be bound when the application is booting.
	 */
	public function withSingletons(array $singletons): static
	{
		return $this->registered(function ($app) use ($singletons) {
			foreach ($singletons as $abstract => $concrete) {
				if (is_string($abstract)) {
					$app->singleton($abstract, $concrete);
				} else {
					$app->singleton($concrete);
				}
			}
		});
	}
}
