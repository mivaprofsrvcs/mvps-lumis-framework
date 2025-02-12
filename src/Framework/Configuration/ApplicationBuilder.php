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

namespace MVPS\Lumis\Framework\Configuration;

use Closure;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Console\Kernel as ConsoleKernel;
use MVPS\Lumis\Framework\Contracts\Console\Kernel as ConsoleKernelContract;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as HttpKernelContract;
use MVPS\Lumis\Framework\Events\EventServiceProvider;
use MVPS\Lumis\Framework\Exceptions\Handler;
use MVPS\Lumis\Framework\Http\Kernel as HttpKernel;
use MVPS\Lumis\Framework\Routing\RouteServiceProvider;

class ApplicationBuilder
{
	/**
	 * Any additional routing callbacks that should be invoked while
	 * registering routes.
	 *
	 * @var array
	 */
	protected array $additionalRoutingCallbacks = [];

	/**
	 * The framework application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Application
	 */
	protected Application $app;

	/**
	 * The page middleware that have been defined by the user.
	 *
	 * @var array
	 */
	protected array $pageMiddleware = [];

	/**
	 * The service provider that are marked for registration.
	 *
	 * @var array
	 */
	protected array $pendingProviders = [];

	/**
	 * Create a new application builder instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
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
								->middleware('web')
								->group($webRoute);
						}
					}
				} else {
					$this->app->make('router')
						->middleware('web')
						->group($web);
				}
			}

			foreach ($this->additionalRoutingCallbacks as $callback) {
				$callback();
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
	 * Register additional Lumis commands with the application.
	 */
	public function withCommands(array $commands = []): static
	{
		if (empty($commands)) {
			$consoleRoutesFile = 'routes/console.php';

			if (is_file($this->app->basePath($consoleRoutesFile))) {
				$commands = [$this->app->basePath($consoleRoutesFile)];
			}

			foreach (['Console/Commands', 'Tasks'] as $commandPath) {
				$path = $this->app->path($commandPath);

				if (! is_dir($path)) {
					continue;
				}

				$commands = [...$commands, $path];
			}
		}

		$this->app->afterResolving(ConsoleKernel::class, function ($kernel) use ($commands) {
			[$commands, $paths] = collection($commands)->partition(fn ($command) => class_exists($command));
			[$routes, $paths] = $paths->partition(fn ($path) => is_file($path));

			$this->app->booted(static function () use ($kernel, $commands, $paths, $routes) {
				$kernel->addCommands($commands->all());
				$kernel->addCommandPaths($paths->all());
				$kernel->addCommandRoutePaths($routes->all());
			});
		});

		return $this;
	}

	/**
	 * Register additional Lumis command route paths.
	 */
	protected function withCommandRouting(array $paths): static
	{
		$this->app->afterResolving(ConsoleKernel::class, function ($kernel) use ($paths) {
			$this->app->booted(fn () => $kernel->addCommandRoutePaths($paths));
		});

		return $this;
	}

	/**
	 * Register the core event service provider for the application.
	 */
	public function withEvents(array|bool $discover = []): static
	{
		if (is_array($discover) && count($discover) > 0) {
			EventServiceProvider::setEventDiscoveryPaths($discover);
		} elseif ($discover === false) {
			EventServiceProvider::disableEventDiscovery();
		}

		if (! isset($this->pendingProviders[EventServiceProvider::class])) {
			$this->app->booting(function () {
				$this->app->register(EventServiceProvider::class);
			});
		}

		$this->pendingProviders[EventServiceProvider::class] = true;

		return $this;
	}

	/**
	 * Register and configure the application's exception handler.
	 */
	public function withExceptions(callable|null $using = null): static
	{
		$this->app->singleton(ExceptionHandler::class, Handler::class);

		$using ??= fn () => true;

		$this->app->afterResolving(
			Handler::class,
			fn ($handler) => $using(new Exceptions($handler))
		);

		return $this;
	}

	/**
	 * Register the standard kernel classes for the application.
	 */
	public function withKernels(): static
	{
		$this->app->singleton(HttpKernelContract::class, HttpKernel::class);

		$this->app->singleton(ConsoleKernelContract::class, ConsoleKernel::class);

		return $this;
	}

	/**
	 * Register the global middleware, middleware groups, and middleware aliases
	 * for the application.
	 */
	public function withMiddleware(callable|null $callback = null): static
	{
		$this->app->afterResolving(HttpKernel::class, function ($kernel) use ($callback) {
			$middleware = new Middleware;
				// TODO: Implement this with authentication system
				// ->redirectGuestsTo(fn () => route('login'));

			if (! is_null($callback)) {
				$callback($middleware);
			}

			$this->pageMiddleware = $middleware->getPageMiddleware();

			$kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
			$kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
			$kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());

			$priorities = $middleware->getMiddlewarePriority();

			if (! empty($priorities)) {
				$kernel->setMiddlewarePriority($priorities);
			}
		});

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
		string|null $commands = null,
		callable|null $then = null
	): static {
		if (is_null($using) && (is_string($web) || is_array($web) || is_callable($then))) {
			$using = $this->buildRoutingCallback($web, $then);
		}

		RouteServiceProvider::loadRoutesUsing($using);

		$this->app->booting(function () {
			$this->app->register(RouteServiceProvider::class, force: true);
		});

		if (is_string($commands) && realpath($commands) !== false) {
			$this->withCommands([$commands]);
		}

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
