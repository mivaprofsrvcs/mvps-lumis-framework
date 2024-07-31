<?php

namespace MVPS\Lumis\Framework\Contracts\Routing\Controllers;

interface HasMiddleware
{
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array;
}
