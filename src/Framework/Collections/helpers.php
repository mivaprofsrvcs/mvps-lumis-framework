<?php

use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;

if (! function_exists('collect')) {
	/**
	 * Create a collection from the given value.
	 */
	function collect(Arrayable|iterable|null $value = []): Collection
	{
		return new Collection($value);
	}
}

if (! function_exists('data_get')) {
	/**
	 * Get an item from an array or object using "dot" notation.
	 */
	function data_get(mixed $target, string|array|int|null $key, mixed $default = null): mixed
	{
		if (is_null($key)) {
			return $target;
		}

		$key = is_array($key) ? $key : explode('.', $key);

		foreach ($key as $index => $segment) {
			unset($key[$index]);

			if (is_null($segment)) {
				return $target;
			}

			if ($segment === '*') {
				if ($target instanceof Collection) {
					$target = $target->all();
				} elseif (! is_iterable($target)) {
					return value($default);
				}

				$result = [];

				foreach ($target as $item) {
					$result[] = data_get($item, $key);
				}

				return in_array('*', $key) ? Arr::collapse($result) : $result;
			}

			if (Arr::accessible($target) && Arr::exists($target, $segment)) {
				$target = $target[$segment];
			} elseif (is_object($target) && property_exists($target, $segment)) {
				$target = $target->{$segment};
			} else {
				return value($default);
			}
		}

		return $target;
	}
}

if (! function_exists('data_set')) {
	/**
	 * Set an item on an array or object using dot notation.
	 */
	function data_set(mixed &$target, string|array $key, mixed $value, bool $overwrite = true): mixed
	{
		$segments = is_array($key) ? $key : explode('.', $key);
		$segment = array_shift($segments);

		if ($segment === '*') {
			if (! Arr::accessible($target)) {
				$target = [];
			}

			if ($segments) {
				foreach ($target as &$inner) {
					data_set($inner, $segments, $value, $overwrite);
				}
			} elseif ($overwrite) {
				foreach ($target as &$inner) {
					$inner = $value;
				}
			}
		} elseif (Arr::accessible($target)) {
			if ($segments) {
				if (! Arr::exists($target, $segment)) {
					$target[$segment] = [];
				}

				data_set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite || ! Arr::exists($target, $segment)) {
				$target[$segment] = $value;
			}
		} elseif (is_object($target)) {
			if ($segments) {
				if (! isset($target->{$segment})) {
					$target->{$segment} = [];
				}

				data_set($target->{$segment}, $segments, $value, $overwrite);
			} elseif ($overwrite || ! isset($target->{$segment})) {
				$target->{$segment} = $value;
			}
		} else {
			$target = [];

			if ($segments) {
				data_set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite) {
				$target[$segment] = $value;
			}
		}

		return $target;
	}
}

if (! function_exists('value')) {
	/**
	 * Return the default value of the given value.
	 */
	function value(mixed $value, mixed ...$args): mixed
	{
		return $value instanceof Closure ? $value(...$args) : $value;
	}
}
