<?php

namespace MVPS\Lumis\Framework\Contracts\Http;

use Throwable;

interface HttpException extends Throwable
{
	/**
	 * Get the response headers.
	 */
	public function getHeaders(): array;

	/**
	 * Get the response status code.
	 */
	public function getStatusCode(): int;
}
