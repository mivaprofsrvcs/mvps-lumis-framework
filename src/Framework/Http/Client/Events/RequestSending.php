<?php

namespace MVPS\Lumis\Framework\Http\Client\Events;

use MVPS\Lumis\Framework\Http\Client\Request;

class RequestSending
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Request
	 */
	public Request $request;

	/**
	 * Create a new request sending event instance.
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}
}
