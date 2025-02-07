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

namespace MVPS\Lumis\Framework\Console\Commands;

use Closure;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Events\Dispatcher;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:list')]
class EventListCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = "List the application's events and listeners";

	/**
	 * The events dispatcher resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected static Closure|null $eventsResolver = null;

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'event:list
		{--event= : Filter the events by name}';

	/**
	 * Add the event implemented interfaces to the output.
	 */
	protected function appendEventInterfaces(string $event): string
	{
		if (! class_exists($event)) {
			return $event;
		}

		$interfaces = class_implements($event);

		if (in_array(ShouldBroadcast::class, $interfaces)) {
			$event .= ' <fg=bright-blue>(ShouldBroadcast)</>';
		}

		return $event;
	}

	/**
	 * Add the listener implemented interfaces to the output.
	 */
	protected function appendListenerInterfaces(string $listener): string
	{
		$listener = explode('@', $listener);

		$interfaces = class_implements($listener[0]);

		$listener = implode('@', $listener);

		if (in_array(ShouldQueue::class, $interfaces)) {
			$listener .= ' <fg=bright-blue>(ShouldQueue)</>';
		}

		return $listener;
	}

	/**
	 * Filter the given events using the provided event name filter.
	 */
	protected function filterEvents(Collection $events): Collection
	{
		$eventName = $this->option('event');

		if (! $eventName) {
			return $events;
		}

		return $events->filter(
			fn ($listeners, $event) => str_contains($event, $eventName)
		);
	}

	/**
	 * Determine if filtering by a specific event name.
	 */
	protected function filteringByEvent(): bool
	{
		return ! empty($this->option('event'));
	}

	/**
	 * Get all of the events and listeners configured for the application.
	 */
	protected function getEvents(): Collection
	{
		$events = collection($this->getListenersOnDispatcher());

		return $this->filteringByEvent()
			? $this->filterEvents($events)
			: $events;
	}

	/**
	 * Get the event dispatcher.
	 */
	public function getEventsDispatcher(): Dispatcher
	{
		return is_null(self::$eventsResolver)
			? $this->getLumis()->make('events')
			: call_user_func(self::$eventsResolver);
	}

	/**
	 * Get the events and event listeners from the dispatcher object.
	 */
	protected function getListenersOnDispatcher(): array
	{
		$events = [];

		foreach ($this->getRawListeners() as $event => $rawListeners) {
			foreach ($rawListeners as $rawListener) {
				if (is_string($rawListener)) {
					$events[$event][] = $this->appendListenerInterfaces($rawListener);
				} elseif ($rawListener instanceof Closure) {
					$events[$event][] = $this->stringifyClosure($rawListener);
				} elseif (is_array($rawListener) && count($rawListener) === 2) {
					if (is_object($rawListener[0])) {
						$rawListener[0] = get_class($rawListener[0]);
					}

					$events[$event][] = $this->appendListenerInterfaces(implode('@', $rawListener));
				}
			}
		}

		return $events;
	}

	/**
	 * Gets the raw version of event listeners from the event dispatcher.
	 */
	protected function getRawListeners(): array
	{
		return $this->getEventsDispatcher()->getRawListeners();
	}

	/**
	 * Execute the event list command.
	 */
	public function handle(): void
	{
		$events = $this->getEvents()->sortKeys();

		if ($events->isEmpty()) {
			$this->components->info('No events were found in your application that match the specified criteria.');

			return;
		}

		$this->newLine();

		$events->each(function ($listeners, $event) {
			$this->components->twoColumnDetail($this->appendEventInterfaces($event));

			$this->components->bulletList($listeners);
		});

		$this->newLine();
	}

	/**
	 * Set a callback that should be used when resolving the events dispatcher.
	 */
	public static function resolveEventsUsing(Closure|null $resolver): void
	{
		static::$eventsResolver = $resolver;
	}

	/**
	 * Get a displayable string representation of a Closure listener.
	 */
	protected function stringifyClosure(Closure $rawListener): string
	{
		$reflection = new ReflectionFunction($rawListener);

		$path = str_replace(
			[base_path(), DIRECTORY_SEPARATOR],
			['', '/'],
			$reflection->getFileName() ?: ''
		);

		return 'Closure at: ' . $path . ':' . $reflection->getStartLine();
	}
}
