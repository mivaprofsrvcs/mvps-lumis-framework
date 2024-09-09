<?php

namespace MVPS\Lumis\Framework\Cache;

use Illuminate\Contracts\Cache\Lock as LockContract;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Support\Sleep;
use MVPS\Lumis\Framework\Support\Str;

abstract class Lock implements LockContract
{
	use InteractsWithTime;

	/**
	 * The name of the lock.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The scope identifier of this lock.
	 *
	 * @var string
	 */
	protected string $owner;

	/**
	 * The number of seconds the lock should be maintained.
	 *
	 * @var int
	 */
	protected int $seconds;

	/**
	 * The number of milliseconds to wait before re-attempting to acquire a
	 * lock while blocking.
	 *
	 * @var int
	 */
	protected int $sleepMilliseconds = 250;

	/**
	 * Create a new lock instance.
	 */
	public function __construct(string $name, int $seconds, string|null $owner = null)
	{
		$this->name = $name;
		$this->owner = $owner ?? Str::random();
		$this->seconds = $seconds;
	}

	/**
	 * Attempt to acquire the lock.
	 */
	abstract public function acquire(): bool;

	/**
	 * Specify the number of milliseconds to sleep in between blocked lock
	 * acquisition attempts.
	 */
	public function betweenBlockedAttemptsSleepFor(int $milliseconds): static
	{
		$this->sleepMilliseconds = $milliseconds;

		return $this;
	}

	/**
	 * Attempt to acquire the lock for the given number of seconds.
	 *
	 * @throws \Illuminate\Contracts\Cache\LockTimeoutException
	 */
	public function block($seconds, $callback = null)
	{
		$starting = ((int) now()->format('Uu')) / 1000;

		$milliseconds = $seconds * 1000;

		while (! $this->acquire()) {
			$now = ((int) now()->format('Uu')) / 1000;

			if (($now + $this->sleepMilliseconds - $milliseconds) >= $starting) {
				throw new LockTimeoutException;
			}

			Sleep::usleep($this->sleepMilliseconds * 1000);
		}

		if (is_callable($callback)) {
			try {
				return $callback();
			} finally {
				$this->release();
			}
		}

		return true;
	}

	/**
	 * Attempt to acquire the lock.
	 */
	public function get($callback = null)
	{
		$result = $this->acquire();

		if ($result && is_callable($callback)) {
			try {
				return $callback();
			} finally {
				$this->release();
			}
		}

		return $result;
	}

	/**
	 * Returns the owner value written into the driver for this lock.
	 *
	 * @return string
	 */
	abstract protected function getCurrentOwner();

	/**
	 * Determine whether this lock is owned by the given identifier.
	 */
	public function isOwnedBy(string|null $owner): bool
	{
		return $this->getCurrentOwner() === $owner;
	}

	/**
	 * Determines whether this lock is allowed to release the lock in the driver.
	 */
	public function isOwnedByCurrentProcess(): bool
	{
		return $this->isOwnedBy($this->owner);
	}

	/**
	 * Returns the current owner of the lock.
	 */
	public function owner(): string
	{
		return $this->owner;
	}

	/**
	 * Release the lock.
	 */
	abstract public function release(): bool;
}
