<?php

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
