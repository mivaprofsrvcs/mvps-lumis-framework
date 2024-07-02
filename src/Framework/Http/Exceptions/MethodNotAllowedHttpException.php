<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class MethodNotAllowedHttpException extends HttpException
{
	/**
	 * Create a method not allowed HTTP exception instance.
	 */
	public function __construct(
		array $allow,
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		$headers['Allow'] = strtoupper(implode(', ', $allow));

		parent::__construct(405, $message, $previous, $headers, $code);
	}
}
