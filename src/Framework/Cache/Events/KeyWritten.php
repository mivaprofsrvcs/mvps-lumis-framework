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

class KeyWritten extends CacheEvent
{
	/**
	 * The number of seconds the key should be valid.
	 *
	 * @var int|null
	 */
	public int|null $seconds;

	/**
	 * The value that will be written.
	 *
	 * @var mixed
	 */
	public mixed $value;

	/**
	 * Create a new key written event instance.
	 */
	public function __construct(
		string|null $storeName,
		string $key,
		mixed $value,
		int|null $seconds = null,
		array $tags = []
	) {
		parent::__construct($storeName, $key, $tags);

		$this->value = $value;
		$this->seconds = $seconds;
	}
}
