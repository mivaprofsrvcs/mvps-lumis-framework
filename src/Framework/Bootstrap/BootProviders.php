<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;
use MVPS\Lumis\Framework\Contracts\Framework\Application;

class BootProviders implements Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$app->boot();
	}
}
