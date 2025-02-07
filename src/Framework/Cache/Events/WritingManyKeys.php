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

class WritingManyKeys extends CacheEvent
{
	/**
	 * The keys that are being written.
	 *
	 * @var mixed
	 */
	public mixed $keys;

	/**
	 * The number of seconds the keys should be valid.
	 *
	 * @var int|null
	 */
	public int|null $seconds;

	/**
	 * The values that will be written.
	 *
	 * @var mixed
	 */
	public mixed $values;

	/**
	 * Create a new writing many keys event instance.
	 */
	public function __construct(
		string|null $storeName,
		array $keys,
		array $values,
		int|null $seconds = null,
		array $tags = []
	) {
		parent::__construct($storeName, $keys[0], $tags);

		$this->keys = $keys;
		$this->values = $values;
		$this->seconds = $seconds;
	}
}
