<?php

namespace MVPS\Lumis\Framework\Database\Connectors;

use Illuminate\Database\Connectors\ConnectionFactory as IlluminateConnectionFactory;
use MVPS\Lumis\Framework\Container\Container;

class ConnectionFactory extends IlluminateConnectionFactory
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected $container;

	/**
	 * Create a new connection factory instance.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}
}
