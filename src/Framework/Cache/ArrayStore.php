<?php

namespace MVPS\Lumis\Framework\Cache;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Cache\Traits\RetrievesMultipleKeys;

class ArrayStore extends TaggableStore implements LockProvider
{
	use InteractsWithTime;
	use RetrievesMultipleKeys;

	/**
	 * The array of locks.
	 *
	 * @var array
	 */
	public array $locks = [];

	/**
	 * Indicates if values are serialized within the store.
	 *
	 * @var bool
	 */
	protected bool $serializesValues;

	/**
	 * The array of stored values.
	 *
	 * @var array
	 */
	protected array $storage = [];

	/**
	 * Create a new array store.
	 */
	public function __construct(bool $serializesValues = false)
	{
		$this->serializesValues = $serializesValues;
	}

	/**
	 * Get the expiration time of the key.
	 */
	protected function calculateExpiration(int $seconds): float
	{
		return $this->toTimestamp($seconds);
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return $this->increment($key, $value * -1);
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush()
	{
		$this->storage = [];

		return true;
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
		if (array_key_exists($key, $this->storage)) {
			unset($this->storage[$key]);

			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		if (! isset($this->storage[$key])) {
			return;
		}

		$item = $this->storage[$key];

		$expiresAt = $item['expiresAt'] ?? 0;

		if ($expiresAt !== 0 && (Carbon::now()->getPreciseTimestamp(3) / 1000) >= $expiresAt) {
			$this->forget($key);

			return;
		}

		return $this->serializesValues
			? unserialize($item['value'])
			: $item['value'];
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
		$existing = $this->get($key);

		if (! is_null($existing)) {
			return tap(
				((int) $existing) + $value,
				function ($incremented) use ($key) {
					$value = $this->serializesValues ? serialize($incremented) : $incremented;

					$this->storage[$key]['value'] = $value;
				}
			);
		}

		$this->forever($key, $value);

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function lock($name, $seconds = 0, $owner = null)
	{
		return new ArrayLock($this, $name, $seconds, $owner);
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $seconds)
	{
		$this->storage[$key] = [
			'value' => $this->serializesValues ? serialize($value) : $value,
			'expiresAt' => $this->calculateExpiration($seconds),
		];

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function restoreLock($name, $owner)
	{
		return $this->lock($name, 0, $owner);
	}

	/**
	 * Get the UNIX timestamp, with milliseconds, for the given number of
	 * seconds in the future.
	 */
	protected function toTimestamp(int $seconds): float
	{
		return $seconds > 0 ?
			(Carbon::now()->getPreciseTimestamp(3) / 1000) + $seconds
			: 0;
	}
}
