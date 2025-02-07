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
