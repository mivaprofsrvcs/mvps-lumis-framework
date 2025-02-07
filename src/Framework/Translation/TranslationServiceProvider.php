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
