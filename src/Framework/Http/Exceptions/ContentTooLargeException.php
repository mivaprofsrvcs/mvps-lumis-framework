<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class ContentTooLargeException extends HttpException
{
	/**
	 * Create a new "Content Too Large" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(413, $message, $previous, $headers, $code);
	}
}
