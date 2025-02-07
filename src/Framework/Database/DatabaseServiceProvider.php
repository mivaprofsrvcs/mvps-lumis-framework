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

namespace MVPS\Lumis\Framework\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use MVPS\Lumis\Framework\Contracts\Queue\EntityResolver;
use MVPS\Lumis\Framework\Database\Connectors\ConnectionFactory;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
	/**
	 * The array of resolved Faker instances.
	 *
	 * @var array
	 */
	protected static array $fakers = [];

	/**
	 * Bootstrap the Eloquent model configurations.
	 */
	public function boot(): void
	{
		Model::setConnectionResolver($this->app['db']);

		Model::setEventDispatcher($this->app['events']);
	}

	/**
	 * Register the database service provider.
	 */
	public function register(): void
	{
		Model::clearBootedModels();

		$this->registerConnectionServices();
		$this->registerFakerGenerator();
		// $this->registerQueueableEntityResolver();
	}

	/**
	 * Register the primary database bindings.
	 */
	protected function registerConnectionServices(): void
	{
		// Registers a connection factory for lazy instantiation of database
		// connections. This approach delays connection creation until
		// explicitly needed, optimizing resource usage.
		$this->app->singleton('db.factory', function ($app) {
			return new ConnectionFactory($app);
		});

		// The database manager is used to resolve various connections, since multiple
		// connections might be managed. It also implements the connection resolver
		// interface which may be used by other components requiring connections.
		$this->app->singleton('db', function ($app) {
			return new DatabaseManager($app, $app['db.factory']);
		});

		$this->app->bind('db.connection', function ($app) {
			return $app['db']->connection();
		});

		$this->app->bind('db.schema', function ($app) {
			return $app['db']->connection()->getSchemaBuilder();
		});

		$this->app->singleton('db.transactions', function ($app) {
			return new DatabaseTransactionsManager;
		});
	}

	/**
	 * Register the Faker Generator instance in the container.
	 */
	protected function registerFakerGenerator(): void
	{
		$this->app->singleton(FakerGenerator::class, function ($app, $parameters) {
			$locale = $parameters['locale'] ?? $app['config']->get('app.faker_locale', 'en_US');

			if (! isset(static::$fakers[$locale])) {
				static::$fakers[$locale] = FakerFactory::create($locale);
			}

			static::$fakers[$locale]->unique(true);

			return static::$fakers[$locale];
		});
	}

	/**
	 * Register the queueable entity resolver implementation.
	 */
	protected function registerQueueableEntityResolver(): void
	{
		$this->app->singleton(EntityResolver::class, function () {
			return new QueueEntityResolver;
		});
	}
}
