<?php

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
