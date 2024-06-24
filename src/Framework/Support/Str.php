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
	 * Determine if a given string matches a given pattern.
	 */
	public static function is(string|iterable $pattern, string $value): bool
	{
		$value = (string) $value;

		if (! is_iterable($pattern)) {
			$pattern = [$pattern];
		}

		foreach ($pattern as $pattern) {
			$pattern = (string) $pattern;

			// If the given value is an exact match we can of course return true right
			// from the beginning. Otherwise, we will translate asterisks and do an
			// actual pattern match against the two strings to see if they match.
			if ($pattern === $value) {
				return true;
			}

			$pattern = preg_quote($pattern, '#');

			// Asterisks are translated into zero-or-more regular expression wildcards
			// to make it convenient to check if the strings starts with the given
			// pattern such as "library/*", making any string check convenient.
			$pattern = str_replace('\*', '.*', $pattern);

			if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
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
