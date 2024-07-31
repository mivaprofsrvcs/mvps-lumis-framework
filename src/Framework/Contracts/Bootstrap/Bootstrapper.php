<?php

namespace MVPS\Lumis\Framework\Contracts\Bootstrap;

use MVPS\Lumis\Framework\Contracts\Framework\Application;

interface Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void;
}
