<?php

namespace MVPS\Lumis\Framework\Events;

use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Container\Container as ContainerContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher as DispatcherContract;

class Dispatcher extends IlluminateEventDispatcher implements DispatcherContract
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * Create a new event dispatcher instance.
	 */
	public function __construct(ContainerContract|null $container = null)
	{
		$this->container = $container ?: new Container;
	}
}
