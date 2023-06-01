<?php

namespace MVPS\Lumis\Framework\Console;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Contracts\Console\Kernel as KernelContract;

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
	 * Create a new console kernel instance.
	 */
	public function __construct(protected Application $app)
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
	 * Handle a console command.
	 */
	public function handle(): void
	{
		$this->bootstrap();
	}
}
