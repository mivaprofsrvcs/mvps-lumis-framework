<?php

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

if (! function_exists('value')) {
	/**
	 * Return the default value of the given value.
	 */
	function value(mixed $value, mixed ...$args): mixed
	{
		return $value instanceof Closure ? $value(...$args) : $value;
	}
}
