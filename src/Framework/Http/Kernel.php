<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\HandleExceptions;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as KernelContract;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\Router;
use Throwable;

class Kernel implements KernelContract
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
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
		HandleExceptions::class,
		RegisterProviders::class,
		BootProviders::class,
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
	 * Get the Lumis application instance.
	 */
	public function getApplication(): Application
	{
		return $this->app;
	}

	/**
	 * Handle an incoming HTTP request.
	 */
	public function handle(Request $request): Response
	{
		try {
			// TODO: Enable HTTP method parameter override (_method)

			$response = $this->sendRequestThroughRouter($request);
		} catch (Throwable $e) {
			$this->reportException($e);

			$response = $this->renderException($request, $e);
		}

		// TODO: Add request handled event dispatch

		return $response;
	}

	/**
	 * Render the exception to a response.
	 */
	protected function renderException(Request $request, Throwable $e): Response
	{
		return $this->app[ExceptionHandler::class]->render($request, $e);
	}

	/**
	 * Report the exception to the exception handler.
	 */
	protected function reportException(Throwable $e): void
	{
		$this->app[ExceptionHandler::class]->report($e);
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

	/**
	 * Set the Lumis application instance.
	 */
	public function setApplication(Application $app)
	{
		$this->app = $app;

		return $this;
	}
}
