<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class LengthRequiredException extends HttpException
{
	/**
	 * Create a new "Length Required" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(411, $message, $previous, $headers, $code);
	}
}
