<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

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
