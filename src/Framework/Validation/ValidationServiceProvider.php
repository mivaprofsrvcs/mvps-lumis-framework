<?php

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
