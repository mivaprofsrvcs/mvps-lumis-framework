<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Cache;

use Illuminate\Contracts\Cache\Store;
use MVPS\Lumis\Framework\Cache\Traits\RetrievesMultipleKeys;

class TaggedCache extends Repository
{
	use RetrievesMultipleKeys {
		putMany as putManyAlias;
	}

	/**
	 * The tag set instance.
	 *
	 * @var \MVPS\Lumis\Framework\Cache\TagSet
	 */
	protected TagSet $tags;

	/**
	 * Create a new tagged cache instance.
	 */
	public function __construct(Store $store, TagSet $tags)
	{
		parent::__construct($store);

		$this->tags = $tags;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function decrement($key, $value = 1)
	{
		return $this->store->decrement($this->itemKey($key), $value);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  \MVPS\Lumis\Framework\Cache\Events\CacheEvent  $event
	 * @return void
	 */
	#[\Override]
	protected function event($event)
	{
		parent::event($event->setTags($this->tags->getNames()));
	}

	/**
	 * Remove all items from the cache.
	 */
	public function flush(): bool
	{
		$this->tags->reset();

		return true;
	}

	/**
	 * Get the tag set instance.
	 */
	public function getTags(): TagSet
	{
		return $this->tags;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function increment($key, $value = 1)
	{
		return $this->store->increment($this->itemKey($key), $value);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function itemKey(string $key): string
	{
		return $this->taggedItemKey($key);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  array  $values
	 * @param  int|null  $ttl
	 * @return bool
	 */
	#[\Override]
	public function putMany(array $values, $ttl = null): bool
	{
		if (is_null($ttl)) {
			return $this->putManyForever($values);
		}

		return $this->putManyAlias($values, $ttl);
	}

	/**
	 * Get a fully qualified key for a tagged item.
	 */
	public function taggedItemKey(string $key): string
	{
		return sha1($this->tags->getNamespace()) . ':' . $key;
	}
}
