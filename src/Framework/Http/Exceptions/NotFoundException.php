<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class NotFoundException extends HttpException
{
	/**
	 * Create a new not found HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(404, $message, $previous, $headers, $code);
	}
}
