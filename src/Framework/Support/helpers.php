<?php

use MVPS\Lumis\Framework\Contracts\Support\DeferringDisplayableValue;
use MVPS\Lumis\Framework\Contracts\Support\Htmlable;
use MVPS\Lumis\Framework\Support\Env;

if (! function_exists('blank')) {
	/**
	 * Determine if the given value is "blank".
	 */
	function blank(mixed $value): bool
	{
		if (is_null($value)) {
			return true;
		}

		if (is_string($value)) {
			return trim($value) === '';
		}

		if (is_numeric($value) || is_bool($value)) {
			return false;
		}

		if ($value instanceof Countable) {
			return count($value) === 0;
		}

		return empty($value);
	}
}

if (! function_exists('dp')) {
	/**
	 * Print variable with optional label.
	 */
	function dp(mixed $data, string $label = ''): void
	{
		$isCli = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
		$eol = $isCli ? PHP_EOL : '<br>';
		$printVariable = is_array($data) || is_object($data);

		if ($isCli) {
			echo $label ? $eol . $label . ':' : '',
				$eol,
				$printVariable ? print_r($data) : $data,
				$eol;
		} else {
			echo $label ? $eol . $label . ':' : '',
				$eol . '<pre>',
				$printVariable ? print_r($data) : $data,
				'<pre>' . $eol;
		}
	}
}

if (! function_exists('dpx')) {
	/**
	 * Print string with all applicable characters converted to HTML entities.
	 */
	function dpx(string $data, string $label = ''): void
	{
		$isCli = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
		$eol = $isCli ? PHP_EOL : '<br>';
		$dataOutput = htmlentities($data);

		if ($isCli) {
			echo $label ? $eol . $label . ':' : '',
				$eol,
				$dataOutput,
				$eol;
		} else {
			echo $label ? $eol . $label . ':' : '',
				$eol . '<pre style="white-space:pre-wrap">',
				$dataOutput,
				'</pre>' . $eol;
		}
	}
}

if (! function_exists('e')) {
	/**
	 * Encode HTML special characters in a string.
	 */
	function e(
		DeferringDisplayableValue|Htmlable|BackedEnum|string|int|float|null $value,
		bool $doubleEncode = true
	): string {
		if ($value instanceof DeferringDisplayableValue) {
			$value = $value->resolveDisplayableValue();
		}

		if ($value instanceof Htmlable) {
			return $value->toHtml();
		}

		if ($value instanceof BackedEnum) {
			$value = $value->value;
		}

		return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
	}
}

if (! function_exists('env')) {
	/**
	 * Gets the value of an environment variable.
	 */
	function env(string $key, mixed $default = null): mixed
	{
		return Env::get($key, $default);
	}
}

if (! function_exists('object_get')) {
	/**
	 * Get an item from an object using "dot" notation.
	 */
	function object_get(object $object, string $key, mixed $default = null): mixed
	{
		if (is_null($key) || trim($key) === '') {
			return $object;
		}

		foreach (explode('.', $key) as $segment) {
			if (! is_object($object) || ! isset($object->{$segment})) {
				return value($default);
			}

			$object = $object->{$segment};
		}

		return $object;
	}
}

if (! function_exists('throw_if')) {
	/**
	 * Throw the given exception if the given condition is true.
	 *
	 * @throws \Throwable
	 */
	function throw_if(mixed $condition, string $exception = 'RuntimeException', mixed ...$parameters): mixed
	{
		if ($condition) {
			if (is_string($exception) && class_exists($exception)) {
				$exception = new $exception(...$parameters);
			}

			throw is_string($exception) ? new RuntimeException($exception) : $exception;
		}

		return $condition;
	}
}

if (! function_exists('throw_unless')) {
	/**
	 * Throw the given exception unless the given condition is true.
	 *
	 * @throws \Throwable
	 */
	function throw_unless(mixed $condition, string $exception = 'RuntimeException', mixed ...$parameters): mixed
	{
		throw_if(! $condition, $exception, ...$parameters);

		return $condition;
	}
}

if (! function_exists('windows_os')) {
	/**
	 * Determine whether the current environment is Windows based.
	 */
	function windows_os(): bool
	{
		return PHP_OS_FAMILY === 'Windows';
	}
}

if (! function_exists('with')) {
	/**
	 * Return the given value, optionally passed through the given callback.
	 */
	function with(mixed $value, callable|null $callback = null): mixed
	{
		return is_null($callback) ? $value : $callback($value);
	}
}
