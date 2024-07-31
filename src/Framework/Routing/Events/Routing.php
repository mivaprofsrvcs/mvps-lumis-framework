<?php

namespace MVPS\Lumis\Framework\Routing\Events;

use MVPS\Lumis\Framework\Http\Request;

class Routing
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	public Request $request;

	/**
	 * Create a new routing event instance.
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}
}
