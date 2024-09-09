<?php

namespace MVPS\Lumis\Framework\Cache;

use Illuminate\Contracts\Cache\Store;

class TagSet
{
	/**
	 * The tag names.
	 *
	 * @var array
	 */
	protected array $names = [];

	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Contracts\Cache\Store
	 */
	protected $store;

	/**
	 * Create a new tag set instance.
	 */
	public function __construct(Store $store, array $names = [])
	{
		$this->store = $store;
		$this->names = $names;
	}

	/**
	 * Flush all the tags in the set.
	 */
	public function flush(): void
	{
		array_walk($this->names, [$this, 'flushTag']);
	}

	/**
	 * Flush the tag from the cache.
	 */
	public function flushTag(string $name): void
	{
		$this->store->forget($this->tagKey($name));
	}

	/**
	 * Get all of the tag names in the set.
	 */
	public function getNames(): array
	{
		return $this->names;
	}

	/**
	 * Get a unique namespace that changes when any of the tags are flushed.
	 */
	public function getNamespace(): string
	{
		return implode('|', $this->tagIds());
	}

	/**
	 * Reset all tags in the set.
	 */
	public function reset(): void
	{
		array_walk($this->names, [$this, 'resetTag']);
	}

	/**
	 * Reset the tag and return the new tag identifier.
	 */
	public function resetTag(string $name): string
	{
		$id = str_replace('.', '', uniqid('', true));

		$this->store->forever($this->tagKey($name), $id);

		return $id;
	}

	/**
	 * Get the unique tag identifier for a given tag.
	 */
	public function tagId(string $name): string
	{
		return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
	}

	/**
	 * Get an array of tag identifiers for all of the tags in the set.
	 */
	protected function tagIds(): array
	{
		return array_map([$this, 'tagId'], $this->names);
	}

	/**
	 * Get the tag identifier key for a given tag.
	 */
	public function tagKey(string $name): string
	{
		return 'tag:' . $name . ':key';
	}
}
