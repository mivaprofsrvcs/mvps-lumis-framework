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

use MVPS\Lumis\Framework\Cache\Traits\RetrievesMultipleKeys;

class ApcStore extends TaggableStore
{
	use RetrievesMultipleKeys;

	/**
	 * The APC wrapper instance.
	 *
	 * @var \MVPS\Lumis\Framework\Cache\ApcWrapper
	 */
	protected ApcWrapper $apc;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * Create a new APC store instance.
	 */
	public function __construct(ApcWrapper $apc, string $prefix = '')
	{
		$this->apc = $apc;
		$this->prefix = $prefix;
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return $this->apc->decrement($this->prefix . $key, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush()
	{
		return $this->apc->flush();
	}

	/**
	 * {@inheritdoc}
	 */
	public function forever($key, $value)
	{
		return $this->put($key, $value, 0);
	}

	/**
	 * {@inheritdoc}
	 */
	public function forget($key)
	{
		return $this->apc->delete($this->prefix . $key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		return $this->apc->get($this->prefix . $key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment($key, $value = 1)
	{
		return $this->apc->increment($this->prefix . $key, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $seconds)
	{
		return $this->apc->put($this->prefix . $key, $value, $seconds);
	}

	/**
	 * Set the cache key prefix.
	 */
	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}
}
