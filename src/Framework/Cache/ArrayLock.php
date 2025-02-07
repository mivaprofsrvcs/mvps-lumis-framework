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

use Carbon\Carbon;

class ArrayLock extends Lock
{
	/**
	 * The parent array cache store.
	 *
	 * @var \MVPS\Lumis\Framework\Cache\ArrayStore
	 */
	protected ArrayStore $store;

	/**
	 * Create a new array lock instance.
	 */
	public function __construct(ArrayStore $store, string $name, int $seconds, string|null $owner = null)
	{
		parent::__construct($name, $seconds, $owner);

		$this->store = $store;
	}

	/**
	 * {@inheritdoc}
	 */
	public function acquire(): bool
	{
		$expiration = $this->store->locks[$this->name]['expiresAt'] ?? Carbon::now()->addSecond();

		if ($this->exists() && $expiration->isFuture()) {
			return false;
		}

		$this->store->locks[$this->name] = [
			'owner' => $this->owner,
			'expiresAt' => $this->seconds === 0 ? null : Carbon::now()->addSeconds($this->seconds),
		];

		return true;
	}

	/**
	 * Determine if the current lock exists.
	 */
	protected function exists(): bool
	{
		return isset($this->store->locks[$this->name]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function forceRelease(): void
	{
		unset($this->store->locks[$this->name]);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCurrentOwner()
	{
		if (! $this->exists()) {
			return null;
		}

		return $this->store->locks[$this->name]['owner'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function release(): bool
	{
		if (! $this->exists() || ! $this->isOwnedByCurrentProcess()) {
			return false;
		}

		$this->forceRelease();

		return true;
	}
}
