<?php

namespace MVPS\Lumis\Framework\Http\Client\Events;

use MVPS\Lumis\Framework\Http\Client\Request;
use MVPS\Lumis\Framework\Http\Client\Response;

class ResponseReceived
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Request
	 */
	public Request $request;

	/**
	 * The response instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Response
	 */
	public Response $response;

	/**
	 * Create a new response received event instance.
	 */
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
}
