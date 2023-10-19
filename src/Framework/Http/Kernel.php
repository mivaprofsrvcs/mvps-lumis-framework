<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as KernelContract;
use MVPS\Lumis\Framework\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

class Kernel implements KernelContract
{
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
	 * Create a new HTTP kernel instance.
	 */
	public function __construct(protected Application $app, protected Router $router)
	{
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
	 */
	public function handle(ServerRequestInterface $request): void
	{
		$this->sendRequestThroughRouter($request);
		exit;
	}

	protected function sendRequestThroughRouter(ServerRequestInterface $request)
	{
		$this->app->instance('request', $request);

		// $this->bootstrap();

		$this->router->dispatch($request);
	}
}
