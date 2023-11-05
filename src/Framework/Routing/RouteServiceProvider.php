<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use MVPS\Lumis\Framework\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * The callback that should be used to load the application's routes.
	 *
	 * @var \Closure|null
	 */
	protected $loadRoutesUsing = null;

	/**
	 * Load the application routes.
	 */
	protected function loadRoutes(): void
	{
		if (! is_null($this->loadRoutesUsing)) {
			$this->app->call($this->loadRoutesUsing);
		} elseif (method_exists($this, 'map')) {
			$this->app->call([$this, 'map']);
		}
	}

	/**
	 * Register application routes service.
	 */
	public function register(): void
	{
		$this->booted(function () {
			$this->loadRoutes();
		});
	}

	/**
	 * Register the callback that will be used to load the application's routes.
	 */
	protected function routes(Closure $routesCallback): static
	{
		$this->loadRoutesUsing = $routesCallback;

		return $this;
	}
}
