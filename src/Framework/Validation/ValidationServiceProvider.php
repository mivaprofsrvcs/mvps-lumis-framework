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

namespace MVPS\Lumis\Framework\Validation;

use Illuminate\Validation\DatabasePresenceVerifier;
use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Register the validation service provider.
	 */
	public function register(): void
	{
		$this->registerPresenceVerifier();
		// TODO: Implement this method
		// $this->registerUncompromisedVerifier();
		$this->registerValidationFactory();
	}

	/**
	 * Register the database presence verifier.
	 */
	protected function registerPresenceVerifier(): void
	{
		$this->app->singleton('validation.presence', function ($app) {
			return new DatabasePresenceVerifier($app['db']);
		});
	}

	/**
	 * Register the validation factory.
	 */
	protected function registerValidationFactory(): void
	{
		$this->app->singleton('validator', function ($app) {
			$validator = new Factory($app['translator'], $app);

			// The validation presence verifier ensures that values exist within
			// a given data collection, typically a relational database or
			// another persistent data store. It also handles "uniqueness"
			// checks, making sure a value does not already exist when required.
			if (isset($app['db'], $app['validation.presence'])) {
				$validator->setPresenceVerifier($app['validation.presence']);
			}

			return $validator;
		});
	}
}
