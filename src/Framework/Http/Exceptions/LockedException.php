<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use Throwable;

class LockedException extends HttpException
{
	/**
	 * Create a new "Locked" HTTP exception instance.
	 */
	public function __construct(
		string $message = '',
		Throwable|null $previous = null,
		int $code = 0,
		array $headers = []
	) {
		parent::__construct(423, $message, $previous, $headers, $code);
	}
}
