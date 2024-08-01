<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class ConflictException extends HttpException
{
	/**
	 * Create a new "Conflict" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(409, $message, $previous, $headers, $code);
	}
}
