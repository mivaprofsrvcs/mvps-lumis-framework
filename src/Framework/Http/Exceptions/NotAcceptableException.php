<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class NotAcceptableException extends HttpException
{
	/**
	 * Create a new access denied HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(403, $message, $previous, $headers, $code);
	}
}
