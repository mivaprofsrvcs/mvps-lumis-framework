<?php

namespace MVPS\Lumis\Framework\Contracts\Http;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

interface Kernel
{
	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void;

	/**
	 * Handle an incoming HTTP request.
	 */
	public function handle(Request $request): Response;
}
