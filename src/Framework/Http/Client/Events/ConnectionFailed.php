<?php

namespace MVPS\Lumis\Framework\Http\Client\Events;

use MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException;
use MVPS\Lumis\Framework\Http\Client\Request;

class ConnectionFailed
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Request
	 */
	public Request $request;

	/**
	 * The exception instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public ConnectionException $exception;

	/**
	 * Create a new connection failed event instance.
	 */
	public function __construct(Request $request, ConnectionException $exception)
	{
		$this->request = $request;
		$this->exception = $exception;
	}
}
