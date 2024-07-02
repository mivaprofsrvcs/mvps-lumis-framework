<?php

use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;

if (! function_exists('collection')) {
	/**
	 * Create a collection from the given value.
	 */
	function collection(Arrayable|iterable|null $value = []): Collection
	{
		return new Collection($value);
	}
}
