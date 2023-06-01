<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;

class RegisterProviders implements Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$app->registerConfiguredProviders();
	}
}
