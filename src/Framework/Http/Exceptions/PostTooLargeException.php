<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class PostTooLargeException extends HttpException
{
	/**
	 * Create a new "post too large" exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		array $headers = [],
		int $code = 0
	) {
		parent::__construct(413, $message, $previous, $headers, $code);
	}
}
