<?php

namespace MVPS\Lumis\Framework\Cache;

class NoLock extends Lock
{
	/**
	 * {@inheritdoc}
	 */
	public function acquire(): bool
	{
		return true;
	}

	/**
	 * Releases this lock in disregard of ownership.
	 */
	public function forceRelease(): null
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCurrentOwner()
	{
		return $this->owner;
	}

	/**
	 * {@inheritdoc}
	 */
	public function release(): bool
	{
		return true;
	}
}
