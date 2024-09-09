<?php

namespace MVPS\Lumis\Framework\Cache\Events;

class RetrievingManyKeys extends CacheEvent
{
	/**
	 * The keys that are being retrieved.
	 *
	 * @var array
	 */
	public array $keys;

	/**
	 * Create a new retrieving many keys event instance.
	 */
	public function __construct(string|null $storeName, array $keys, array $tags = [])
	{
		parent::__construct($storeName, $keys[0] ?? '', $tags);

		$this->keys = $keys;
	}
}
