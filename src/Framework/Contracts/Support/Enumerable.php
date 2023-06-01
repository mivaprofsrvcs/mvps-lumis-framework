<?php

namespace MVPS\Lumis\Framework\Contracts\Support;

use Countable;
use JsonSerializable;
use IteratorAggregate;

// TODO: implement all methods
interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
	/**
	 * Determine if an item exists in the collection by key.
	 */
	public function has(string $key): bool;
}
