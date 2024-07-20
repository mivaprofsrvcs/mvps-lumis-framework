<?php

namespace MVPS\Lumis\Framework\Events;

use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher as DispatcherContract;

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
	public function __construct(Container|null $container = null)
	{
		$this->container = $container ?: new Container;
	}
}
