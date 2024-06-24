<?php

namespace MVPS\Lumis\Framework\Routing;

use Closure;
use MVPS\Lumis\Framework\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * The global callback that should be used to load the application's routes.
	 *
	 * @var \Closure|null
	 */
	protected static Closure|null $alwaysLoadRoutesUsing;

	/**
	 * The callback that should be used to load the application's routes.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $loadRoutesUsing = null;

	/**
	 * Load the application routes.
	 */
	protected function loadRoutes(): void
	{
		if (! is_null(self::$alwaysLoadRoutesUsing)) {
			$this->app->call(self::$alwaysLoadRoutesUsing);
		}

		if (! is_null($this->loadRoutesUsing)) {
			$this->app->call($this->loadRoutesUsing);
		} elseif (method_exists($this, 'map')) {
			$this->app->call([$this, 'map']);
		}
	}

	/**
	 * Register the callback that will be used to load the application's routes.
	 */
	public static function loadRoutesUsing(Closure|null $routesCallback): void
	{
		self::$alwaysLoadRoutesUsing = $routesCallback;
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
