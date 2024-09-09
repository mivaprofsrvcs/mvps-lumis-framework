<?php

namespace MVPS\Lumis\Framework\Cache\Events;

abstract class CacheEvent
{
	/**
	 * The key of the event.
	 *
	 * @var string
	 */
	public string $key;

	/**
	 * The name of the cache store.
	 *
	 * @var string|null
	 */
	public string|null $storeName;

	/**
	 * The tags that were assigned to the key.
	 *
	 * @var array
	 */
	public array $tags;

	/**
	 * Create a new cache event instance.
	 */
	public function __construct(string|null $storeName, string $key, array $tags = [])
	{
		$this->storeName = $storeName;
		$this->key = $key;
		$this->tags = $tags;
	}

	/**
	 * Set the tags for the cache event.
	 */
	public function setTags(array $tags): static
	{
		$this->tags = $tags;

		return $this;
	}
}
