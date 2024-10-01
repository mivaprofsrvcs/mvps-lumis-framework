<?php

namespace MVPS\Lumis\Framework\Translation;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return ['translator', 'translation.loader'];
	}

	/**
	 * Register the translation service provider.
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->registerLoader();

		$this->app->singleton('translator', function ($app) {
			return new Translator($app['translation.loader']);
		});
	}

	/**
	 * Register the translation line loader.
	 */
	protected function registerLoader(): void
	{
		$this->app->singleton('translation.loader', function ($app) {
			return new FileLoader(
				$app['files'],
				[__DIR__ . '/translations', $app['path.translations']]
			);
		});
	}
}
