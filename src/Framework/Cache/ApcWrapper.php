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

class ApcWrapper
{
	/**
	 * Decrement the value of an item in the cache.
	 */
	public function decrement(string $key, mixed $value): int|bool
	{
		return apcu_dec($key, $value);
	}

	/**
	 * Remove an item from the cache.
	 */
	public function delete(string $key): bool
	{
		return apcu_delete($key);
	}

	/**
	 * Remove all items from the cache.
	 */
	public function flush(): bool
	{
		return apcu_clear_cache();
	}

	/**
	 * Get an item from the cache.
	 */
	public function get(string $key): mixed
	{
		$fetchedValue = apcu_fetch($key, $success);

		return $success ? $fetchedValue : null;
	}

	/**
	 * Increment the value of an item in the cache.
	 */
	public function increment(string $key, mixed $value): int|bool
	{
		return apcu_inc($key, $value);
	}

	/**
	 * Store an item in the cache.
	 */
	public function put(string $key, mixed $value, int $seconds): array|bool
	{
		return apcu_store($key, $value, $seconds);
	}
}
