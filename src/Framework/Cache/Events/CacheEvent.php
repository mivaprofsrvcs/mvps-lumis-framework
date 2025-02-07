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
