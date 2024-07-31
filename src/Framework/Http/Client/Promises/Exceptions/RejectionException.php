<?php

namespace MVPS\Lumis\Framework\Http\Client\Promises\Exceptions;

use JsonSerializable;
use RuntimeException;

class RejectionException extends RuntimeException
{
	/**
	 * The rejection reason
	 *
	 * @var mixed
	 */
	private mixed $reason;

	/**
	 * Create a new rejection exception instance.
	 */
	public function __construct(mixed $reason, string|null $description = null)
	{
		$this->reason = $reason;

		$message = 'The promise was rejected';

		if ($description) {
			$message .= ' with reason: ' . $description;
		} elseif (is_string($reason) || (is_object($reason) && method_exists($reason, '__toString'))) {
			$message .= ' with reason: ' . $this->reason;
		} elseif ($reason instanceof JsonSerializable) {
			$message .= ' with reason: ' . json_encode($this->reason, JSON_PRETTY_PRINT);
		}

		parent::__construct($message);
	}

	/**
	 * Returns the rejection reason.
	 */
	public function getReason(): mixed
	{
		return $this->reason;
	}
}
