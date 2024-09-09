<?php

namespace MVPS\Lumis\Framework\Cache\Events;

class CacheHit extends CacheEvent
{
	/**
	 * The value that was retrieved.
	 *
	 * @var mixed
	 */
	public mixed $value;

	/**
	 * Create a new cache hit event instance.
	 */
	public function __construct(string|null $storeName, string $key, mixed $value, array $tags = [])
	{
		parent::__construct($storeName, $key, $tags);

		$this->value = $value;
	}
}
