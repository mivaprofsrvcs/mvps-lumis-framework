<?php

namespace MVPS\Lumis\Framework\Collections;

use ArrayAccess;
use MVPS\Lumis\Framework\Contracts\Support\Enumerable;

class Arr
{
	/**
	 * Determine whether the given value is array accessible.
	 */
	public static function accessible(mixed $value): bool
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Collapse an array of arrays into a single array.
	 */
	public static function collapse(iterable $array): array
	{
		$results = [];

		foreach ($array as $values) {
			if ($values instanceof Collection) {
				$values = $values->all();
			} elseif (! is_array($values)) {
				continue;
			}

			$results[] = $values;
		}

		return array_merge([], ...$results);
	}

	/**
	 * Determine if the given key exists in the provided array.
	 */
	public static function exists(ArrayAccess|array $array, string|int $key): bool
	{
		if ($array instanceof Enumerable) {
			return $array->has($key);
		}

		if ($array instanceof ArrayAccess) {
			return $array->offsetExists($key);
		}

		if (is_float($key)) {
			$key = (string) $key;
		}

		return array_key_exists($key, $array);
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 */
	public static function first(iterable $array, callable $callback = null, mixed $default = null): mixed
	{
		if (is_null($callback)) {
			if (empty($array)) {
				return value($default);
			}

			foreach ($array as $item) {
				return $item;
			}
		}

		foreach ($array as $key => $value) {
			if ($callback($value, $key)) {
				return $value;
			}
		}

		return value($default);
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 */
	public static function forget(array &$array, array|string|int|float $keys): void
	{
		$original = &$array;

		$keys = (array) $keys;

		if (count($keys) === 0) {
			return;
		}

		foreach ($keys as $key) {
			// If the exact key exists in the top-level, remove it
			if (static::exists($array, $key)) {
				unset($array[$key]);

				continue;
			}

			$parts = explode('.', $key);

			$array = &$original;

			while (count($parts) > 1) {
				$part = array_shift($parts);

				if (isset($array[$part]) && static::accessible($array[$part])) {
					$array = &$array[$part];
				} else {
					continue 2;
				}
			}

			unset($array[array_shift($parts)]);
		}
	}

	/**
	 * Get an item from an array using "dot" notation.
	 */
	public static function get(ArrayAccess|array $array, string|int|null $key, mixed $default = null): mixed
	{
		if (! static::accessible($array)) {
			return value($default);
		}

		if (is_null($key)) {
			return $array;
		}

		if (static::exists($array, $key)) {
			return $array[$key];
		}

		if (! str_contains($key, '.')) {
			return $array[$key] ?? value($default);
		}

		foreach (explode('.', $key) as $segment) {
			if (static::accessible($array) && static::exists($array, $segment)) {
				$array = $array[$segment];
			} else {
				return value($default);
			}
		}

		return $array;
	}

	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 */
	public static function has(ArrayAccess|array $array, string|array $keys): bool
	{
		$keys = (array) $keys;

		if (! $array || $keys === []) {
			return false;
		}

		foreach ($keys as $key) {
			$subKeyArray = $array;

			if (static::exists($array, $key)) {
				continue;
			}

			foreach (explode('.', $key) as $segment) {
				if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
					$subKeyArray = $subKeyArray[$segment];
				} else {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Determine if any of the keys exist in an array using "dot" notation.
	 */
	public static function hasAny(ArrayAccess|array $array, string|array|null $keys): bool
	{
		if (is_null($keys)) {
			return false;
		}

		if (! $array) {
			return false;
		}

		$keys = (array) $keys;

		if ($keys === []) {
			return false;
		}

		foreach ($keys as $key) {
			if (static::has($array, $key)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 * If no key is given to the method, the entire array will be replaced.
	 */
	public static function set(array &$array, string|int|null $key, mixed $value): array
	{
		if (is_null($key)) {
			return $array = $value;
		}

		$keys = explode('.', $key);

		foreach ($keys as $index => $key) {
			if (count($keys) === 1) {
				break;
			}

			unset($keys[$index]);

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if (! isset($array[$key]) || ! is_array($array[$key])) {
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}

	/**
	 * Filter the array using the given callback.
	 */
	public static function where(array $array, callable $callback): array
	{
		return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
	}
}
