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

namespace MVPS\Lumis\Framework\Exceptions\Console;

class ArgumentFormatter
{
	/**
	 * Maximum allowed length for string arguments.
	 *
	 * @var int
	 */
	protected const MAX_STRING_LENGTH = 1000;

	/**
	 * Formats an array of arguments into a human-readable string.
	 *
	 * Recursively formats array elements if the recursive flag is set to true.
	 * Truncates strings exceeding the maximum length.
	 */
	public function format(array $arguments, bool $recursive = true): string
	{
		$result = [];

		foreach ($arguments as $argument) {
			if (is_string($argument)) {
				$result[] = '"' . (
						mb_strlen($argument) > self::MAX_STRING_LENGTH
							? mb_substr($argument, 0, self::MAX_STRING_LENGTH) . '...'
							: $argument
					) . '"';
			} elseif (is_array($argument)) {
				$associative = array_keys($argument) !== range(0, count($argument) - 1);

				if ($recursive && $associative && count($argument) <= 5) {
					$result[] = '[' . $this->format($argument, false) . ']';
				}
			} elseif (is_object($argument)) {
				$result[] = 'Object(' . get_class($argument) . ')';
			}
		}

		return implode(', ', $result);
	}
}
