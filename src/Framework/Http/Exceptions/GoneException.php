<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class GoneException extends HttpException
{
	/**
	 * Create a new "Gone" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(410, $message, $previous, $headers, $code);
	}
}
