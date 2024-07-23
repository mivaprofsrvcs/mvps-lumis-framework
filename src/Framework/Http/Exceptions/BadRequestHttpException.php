<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class BadRequestHttpException extends HttpException
{
	/**
	 * Create a new bad request HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(400, $message, $previous, $headers, $code);
	}
}