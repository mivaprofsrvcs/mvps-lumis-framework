<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as KernelContract;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\Router;

class Kernel implements KernelContract
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Application
	 */
	protected Application $app;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var string[]
	 */
	protected array $bootstrappers = [
		LoadEnvironmentVariables::class,
		LoadConfiguration::class,
		RegisterProviders::class,
		BootProviders::class,
		// TODO: Implement these
		// \MVPS\Lumis\Framework\Bootstrap\HandleExceptions::class,
	];

	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * Create a new HTTP kernel instance.
	 */
	public function __construct(Application $app, Router $router)
	{
		$this->app = $app;
		$this->router = $router;
	}

	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void
	{
		if ($this->app->hasBeenBootstrapped()) {
			return;
		}

		$this->app->bootstrapWith($this->bootstrappers());
	}

	/**
	 * Get the bootstrap classes for the application.
	 */
	protected function bootstrappers(): array
	{
		return $this->bootstrappers;
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * TODO: Add try/catch handling for sendRequestThroughRouter() method.
	 */
	public function handle(Request $request): Response
	{
		return $this->sendRequestThroughRouter($request);
	}

	/**
	 * Send the given request through the router.
	 */
	protected function sendRequestThroughRouter(Request $request): Response
	{
		$this->app->instance('request', $request);

		$this->bootstrap();

		return $this->router->dispatch($request);
	}
}
