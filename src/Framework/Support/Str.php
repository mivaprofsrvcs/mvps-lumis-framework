<?php

namespace MVPS\Lumis\Framework\Support;

use JsonException;

class Str
{
	/**
	 * Determine if a given string contains a given substring.
	 */
	public static function contains(string $haystack, string|iterable $needles, bool $ignoreCase = false): bool
	{
		if ($ignoreCase) {
			$haystack = mb_strtolower($haystack);
		}

		if (! is_iterable($needles)) {
			$needles = (array) $needles;
		}

		foreach ($needles as $needle) {
			if ($ignoreCase) {
				$needle = mb_strtolower($needle);
			}

			if ($needle !== '' && str_contains($haystack, $needle)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string is valid JSON.
	 */
	public static function isJson(string $value): bool
	{
		if (! is_string($value)) {
			return false;
		}

		try {
			json_decode($value, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return false;
		}

		return true;
	}

	/**
	 * Parse a Class[@]method style callback into class and method.
	 */
	public static function parseCallback(string $callback, string|null $default = null): array
	{
		return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
	}
}
