<?php

namespace MVPS\Lumis\Framework\Routing\Events;

use MVPS\Lumis\Framework\Http\Request;

class PreparingResponse
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	public Request $request;

	/**
	 * The response.
	 *
	 * @var mixed
	 */
	public mixed $response;

	/**
	 * Create a new preparing response routing event instance.
	 */
	public function __construct(Request $request, mixed $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
}
