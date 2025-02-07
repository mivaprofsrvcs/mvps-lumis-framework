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

class CacheLock extends Lock
{
	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Contracts\Cache\Store
	 */
	protected Store $store;

	/**
	 * Create a new cache lock instance.
	 */
	public function __construct(Store $store, string $name, int $seconds, string|null $owner = null)
	{
		parent::__construct($name, $seconds, $owner);

		$this->store = $store;
	}

	/**
	 * {@inheritdoc}
	 */
	public function acquire(): bool
	{
		if (method_exists($this->store, 'add') && $this->seconds > 0) {
			return $this->store->add($this->name, $this->owner, $this->seconds);
		}

		if (! is_null($this->store->get($this->name))) {
			return false;
		}

		return $this->seconds > 0
			? $this->store->put($this->name, $this->owner, $this->seconds)
			: $this->store->forever($this->name, $this->owner);
	}

	/**
	 * {@inheritdoc}
	 */
	public function forceRelease()
	{
		$this->store->forget($this->name);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCurrentOwner()
	{
		return $this->store->get($this->name);
	}

	/**
	 * {@inheritdoc}
	 */
	public function release(): bool
	{
		if ($this->isOwnedByCurrentProcess()) {
			return $this->store->forget($this->name);
		}

		return false;
	}
}
