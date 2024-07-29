<?php

namespace MVPS\Lumis\Framework\Routing\Events;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Route;

class RouteMatched
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	public Request $request;

	/**
	 * The route instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Route
	 */
	public Route $route;

	/**
	 * Create a new route matched routing event instance.
	 */
	public function __construct(Route $route, Request $request)
	{
		$this->route = $route;
		$this->request = $request;
	}
}
