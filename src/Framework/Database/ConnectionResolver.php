<?php

namespace MVPS\Lumis\Framework\Database;

use Illuminate\Database\ConnectionResolver as IlluminateConnectionResolver;
use MVPS\Lumis\Framework\Contracts\Database\ConnectionResolver as ConnectionResolverContract;

class ConnectionResolver extends IlluminateConnectionResolver implements ConnectionResolverContract
{
	/**
	 * All of the registered connections.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Database\ConnectionResolver[]
	 */
	protected $connections = [];
}
