<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class TooManyRequestsException extends HttpException
{
	/**
	 * Create a new "Too Many Requests" HTTP exception instance.
	 */
	public function __construct(
		int|string|null $retryAfter = null,
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		if ($retryAfter) {
			$headers['Retry-After'] = $retryAfter;
		}

		parent::__construct(429, $message, $previous, $headers, $code);
	}
}
