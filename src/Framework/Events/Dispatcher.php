<?php

namespace MVPS\Lumis\Framework\Events;

use Closure;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Support\Str;

class Dispatcher
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected Container $container;

	/**
	 * The registered event listeners.
	 *
	 * @var array
	 */
	protected array $listeners = [];

	/**
	 * Create a new event dispatcher instance.
	 */
	public function __construct(Container|null $container = null)
	{
		$this->container = $container ?: new Container;
	}

	/**
	 * Add the listeners for the event's interfaces to the given array.
	 */
	protected function addInterfaceListeners(string $eventName, array $listeners = []): array
	{
		foreach (class_implements($eventName) as $interface) {
			if (! isset($this->listeners[$interface])) {
				continue;
			}

			foreach ($this->prepareListeners($interface) as $names) {
				$listeners = array_merge($listeners, (array) $names);
			}
		}

		return $listeners;
	}

	/**
	 * Create the class based event callable.
	 */
	protected function createClassCallable(array|string $listener): callable
	{
		[$class, $method] = is_array($listener)
			? $listener
			: $this->parseClassCallable($listener);

		if (! method_exists($class, $method)) {
			$method = '__invoke';
		}

		$listener = $this->container->make($class);

		return [$listener, $method];
	}

	/**
	 * Create a class based listener using the IoC container.
	 */
	public function createClassListener(string $listener, bool $wildcard = false): Closure
	{
		return function ($event, $payload) use ($listener, $wildcard) {
			if ($wildcard) {
				return call_user_func($this->createClassCallable($listener), $event, $payload);
			}

			$callable = $this->createClassCallable($listener);

			return $callable(...array_values($payload));
		};
	}

	/**
	 * Fire an event and call the listeners.
	 */
	public function dispatch(string|object $event, mixed $payload = [], bool $halt = false): array|null
	{
		[$event, $payload] = [
			is_object($event),
			...$this->parseEventAndPayload($event, $payload),
		];

		return $this->invokeListeners($event, $payload, $halt);
	}

	/**
	 * Get all of the listeners for a given event name.
	 */
	public function getListeners(string $eventName): array
	{
		$listeners = $this->prepareListeners($eventName);

		return class_exists($eventName, false)
			? $this->addInterfaceListeners($eventName, $listeners)
			: $listeners;
	}

	/**
	 * Call listeners for an event.
	 */
	protected function invokeListeners(string|object $event, mixed $payload, bool $halt = false): array|null
	{
		$responses = [];

		foreach ($this->getListeners($event) as $listener) {
			$response = $listener($event, $payload);

			if ($halt && ! is_null($response)) {
				return $response;
			}

			// If a boolean false is returned from a listener, we will stop propagating
			// the event to any further listeners down in the chain, else we keep on
			// looping through the listeners and firing every one in our sequence.
			if ($response === false) {
				break;
			}

			$responses[] = $response;
		}

		return $halt ? null : $responses;
	}

	/**
	 * Register an event listener with the dispatcher.
	 */
	public function makeListener(Closure|string|array $listener, bool $wildcard = false): Closure
	{
		if (is_string($listener) || (is_array($listener) && isset($listener[0]) && is_string($listener[0]))) {
			return $this->createClassListener($listener, $wildcard);
		}

		return function ($event, $payload) use ($listener, $wildcard) {
			if ($wildcard) {
				return $listener($event, $payload);
			}

			return $listener(...array_values($payload));
		};
	}

	/**
	 * Parse the class listener into class and method.
	 */
	protected function parseClassCallable(string $listener): array
	{
		return Str::parseCallback($listener, 'handle');
	}

	/**
	 * Parse the given event and payload and prepare them for dispatching.
	 */
	protected function parseEventAndPayload(mixed $event, mixed $payload): array
	{
		if (is_object($event)) {
			[$payload, $event] = [[$event], get_class($event)];
		}

		return [$event, Arr::wrap($payload)];
	}

	/**
	 * Prepare the listeners for a given event.
	 */
	protected function prepareListeners(string $eventName): array
	{
		$listeners = [];

		foreach ($this->listeners[$eventName] ?? [] as $listener) {
			$listeners[] = $this->makeListener($listener);
		}

		return $listeners;
	}
}
