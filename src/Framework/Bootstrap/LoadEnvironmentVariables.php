<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use Dotenv\Dotenv;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Support\Env;
use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;

class LoadEnvironmentVariables implements Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$this->createDotenv($app)
			->safeLoad();
	}

	/**
	 * Create a Dotenv instance.
	 */
	protected function createDotenv(Application $app): Dotenv
	{
		return Dotenv::create(Env::getRepository(), $app->environmentPath(), $app->environmentFile());
	}
}
