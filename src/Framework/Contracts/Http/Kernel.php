<?php

namespace MVPS\Lumis\Framework\Contracts\Http;

use Psr\Http\Message\ServerRequestInterface;

interface Kernel
{
	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void;

	/**
	 * Handle an incoming HTTP request.
	 */
	public function handle(ServerRequestInterface $request): void;
}
