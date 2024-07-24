<?php

namespace MVPS\Lumis\Framework\Contracts\Http;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

interface Responsable
{
	/**
	 * Create an HTTP response that represents the object.
	 */
	public function toResponse(Request $request): Response;
}
