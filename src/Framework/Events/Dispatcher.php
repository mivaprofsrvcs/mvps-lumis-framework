<?php

namespace MVPS\Lumis\Framework\Events;

use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher as DispatcherContract;
use MVPS\Lumis\Framework\Support\Str;

class Dispatcher extends IlluminateEventDispatcher implements DispatcherContract
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected $container;

	/**
	 * Create a new event dispatcher instance.
	 */
	#[\Override]
	public function __construct(Container|null $container = null)
	{
		$this->container = $container ?: new Container;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function parseClassCallable($listener): array
	{
		return Str::parseCallback($listener, 'handle');
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function parseEventAndPayload(mixed $event, mixed $payload): array
	{
		if (is_object($event)) {
			[$payload, $event] = [[$event], get_class($event)];
		}

		return [$event, Arr::wrap($payload)];
	}
}
