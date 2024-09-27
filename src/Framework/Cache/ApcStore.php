<?php

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
