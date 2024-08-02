<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class AccessDeniedException extends HttpException
{
	/**
	 * Create a new "Access Denied" HTTP exception instance.
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
