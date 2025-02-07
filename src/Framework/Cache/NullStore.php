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

use Illuminate\Contracts\Cache\LockProvider;
use MVPS\Lumis\Framework\Cache\Traits\RetrievesMultipleKeys;

class NullStore extends TaggableStore implements LockProvider
{
	use RetrievesMultipleKeys;

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forever($key, $value)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forget($key)
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPrefix(): string
	{
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment($key, $value = 1)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function lock($name, $seconds = 0, $owner = null)
	{
		return new NoLock($name, $seconds, $owner);
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $seconds)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function restoreLock($name, $owner)
	{
		return $this->lock($name, 0, $owner);
	}
}
