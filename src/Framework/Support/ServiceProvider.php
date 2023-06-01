<?php

namespace MVPS\Lumis\Framework\Support;

use Closure;
use MVPS\Lumis\Framework\Application;

abstract class ServiceProvider
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Application
	 */
	protected Application $app;

	/**
	 * All of the registered booted callbacks.
	 */
	protected array $bootedCallbacks = [];

	/**
	 * All of the registered booting callbacks.
	 *
	 * @var array
	 */
	protected array $bootingCallbacks = [];

	/**
	 * Create a new service provider instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Register a booted callback to be run after the "boot" method is called.
	 */
	public function booted(Closure $callback): void
	{
		$this->bootedCallbacks[] = $callback;
	}

	/**
	 * Register a booting callback to be run before the "boot" method is called.
	 */
	public function booting(Closure $callback): void
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Call the registered booted callbacks.
	 */
	public function callBootedCallbacks(): void
	{
		$index = 0;

		while ($index < count($this->bootedCallbacks)) {
			$this->app->call($this->bootedCallbacks[$index]);

			$index++;
		}
	}

	/**
	 * Call the registered booting callbacks.
	 */
	public function callBootingCallbacks(): void
	{
		$index = 0;

		while ($index < count($this->bootingCallbacks)) {
			$this->app->call($this->bootingCallbacks[$index]);

			$index++;
		}
	}

	/**
	 * Register any application services.
	 */
	public function register(): void
	{
	}
}
