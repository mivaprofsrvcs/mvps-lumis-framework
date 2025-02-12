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

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Providers\ServiceProvider;
use MVPS\Lumis\Framework\Support\Arr;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * The configured event discovery paths.
	 *
	 * @var array|null
	 */
	protected static array|null $eventDiscoveryPaths = null;

	/**
	 * The event handler mappings for the application.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $listen = [];

	/**
	 * The model observers to register.
	 *
	 * @var array<string, string|object|array<int, string|object>>
	 */
	protected array $observers = [];

	/**
	 * Indicates if events should be discovered.
	 *
	 * @var bool
	 */
	protected static bool $shouldDiscoverEvents = true;

	/**
	 * The subscribers to register.
	 *
	 * @var array
	 */
	protected array $subscribe = [];

	/**
	 * Add the given event discovery paths to the application's event discovery
	 * paths.
	 */
	public static function addEventDiscoveryPaths(array|string $paths): void
	{
		static::$eventDiscoveryPaths = array_values(array_unique(
			array_merge(static::$eventDiscoveryPaths, Arr::wrap($paths))
		));
	}

	/**
	 * Boot any application services.
	 */
	public function boot(): void
	{
	}

	/**
	 * Disable event discovery for the application.
	 */
	public static function disableEventDiscovery(): void
	{
		static::$shouldDiscoverEvents = false;
	}

	/**
	 * Discover the events and listeners for the application.
	 */
	public function discoverEvents(): array
	{
		return collection($this->discoverEventsWithin())
			->reject(function ($directory) {
				return ! is_dir($directory);
			})
			->reduce(function ($discovered, $directory) {
				return array_merge_recursive(
					$discovered,
					DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
				);
			}, []);
	}

	/**
	 * Get the listener directories that should be used to discover events.
	 */
	protected function discoverEventsWithin(): array
	{
		return static::$eventDiscoveryPaths ?: [
			$this->app->path('Listeners'),
		];
	}

	/**
	 * Get the discovered events for the application.
	 */
	protected function discoveredEvents(): array
	{
		return $this->shouldDiscoverEvents()
			? $this->discoverEvents()
			: [];
	}

	/**
	 * Get the base path to be used during event discovery.
	 */
	protected function eventDiscoveryBasePath(): string
	{
		return base_path();
	}

	/**
	 * Get the discovered events and listeners for the application.
	 */
	public function getEvents(): array
	{
		if ($this->app->eventsAreCached()) {
			$cache = require $this->app->getCachedEventsPath();

			return $cache[get_class($this)] ?? [];
		} else {
			return array_merge_recursive(
				$this->discoveredEvents(),
				$this->listens()
			);
		}
	}

	/**
	 * Get the events and handlers.
	 */
	public function listens(): array
	{
		return $this->listen;
	}

	/**
	 * Register the event listener service provider.
	 */
	public function register(): void
	{
		$this->booting(function () {
			$events = $this->getEvents();

			foreach ($events as $event => $listeners) {
				foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
					$this->app['events']->listen($event, $listener);
				}
			}

			foreach ($this->subscribe as $subscriber) {
				$this->app['events']->subscribe($subscriber);
			}

			foreach ($this->observers as $model => $observers) {
				$model::observe($observers);
			}
		});
	}

	/**
	 * Set the globally configured event discovery paths.
	 */
	public static function setEventDiscoveryPaths(array $paths): void
	{
		static::$eventDiscoveryPaths = $paths;
	}

	/**
	 * Determine if events and listeners should be automatically discovered.
	 */
	public function shouldDiscoverEvents(): bool
	{
		return get_class($this) === __CLASS__ &&
			static::$shouldDiscoverEvents === true;
	}
}
