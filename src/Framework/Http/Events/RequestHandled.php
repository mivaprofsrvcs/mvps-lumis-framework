<?php

namespace MVPS\Lumis\Framework\Http\Events;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class RequestHandled
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	public Request $request;

	/**
	 * The response instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Response
	 */
	public Response $response;

	/**
	 * Create a new request handled event instance.
	 */
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
}
