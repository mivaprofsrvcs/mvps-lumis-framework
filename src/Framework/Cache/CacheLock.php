<?php

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
