<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class PreconditionRequiredException extends HttpException
{
	/**
	 * Create a new "Precondition Required" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(428, $message, $previous, $headers, $code);
	}
}
