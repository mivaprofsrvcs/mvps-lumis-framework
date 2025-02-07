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

namespace MVPS\Lumis\Framework\Cache\Traits;

trait RetrievesMultipleKeys
{
	/**
	 * Retrieve multiple items from the cache by key.
	 *
	 * Items not found in the cache will have a null value.
	 */
	public function many(array $keys): array
	{
		$return = [];

		$keys = collection($keys)
			->mapWithKeys(
				fn ($value, $key) => [is_string($key) ? $key : $value => is_string($key) ? $value : null]
			)
			->all();

		foreach ($keys as $key => $default) {
			/** @phpstan-ignore arguments.count (some clients don't accept a default) */
			$return[$key] = $this->get($key, $default);
		}

		return $return;
	}

	/**
	 * Store multiple items in the cache for a given number of seconds.
	 */
	public function putMany(array $values, int $seconds): bool
	{
		$manyResult = null;

		foreach ($values as $key => $value) {
			$result = $this->put($key, $value, $seconds);

			$manyResult = is_null($manyResult) ? $result : $result && $manyResult;
		}

		return $manyResult ?: false;
	}
}
