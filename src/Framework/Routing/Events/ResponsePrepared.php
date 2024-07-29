<?php

namespace MVPS\Lumis\Framework\Routing\Events;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class ResponsePrepared
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
	 * Create a new response prepared routing event instance.
	 */
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
}
