<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class PreconditionFailedException extends HttpException
{
	/**
	 * Create a new "Precondition Failed" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(412, $message, $previous, $headers, $code);
	}
}
