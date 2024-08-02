<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class UnprocessableEntityException extends HttpException
{
	/**
	 * Create a new "Unsupported Media Type" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(422, $message, $previous, $headers, $code);
	}
}
