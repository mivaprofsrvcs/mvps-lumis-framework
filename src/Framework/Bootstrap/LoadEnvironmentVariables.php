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

namespace MVPS\Lumis\Framework\Bootstrap;

use Dotenv\Dotenv;
use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Support\Env;

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
