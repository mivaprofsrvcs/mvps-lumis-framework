<?php

namespace MVPS\Lumis\Framework\Cache;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

class DatabaseLock extends Lock
{
	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	protected Connection $connection;

	/**
	 * The default number of seconds that a lock should be held.
	 *
	 * @var int
	 */
	protected int $defaultTimeoutInSeconds;

	/**
	 * The prune probability odds.
	 *
	 * @var array
	 */
	protected array $lottery;

	/**
	 * The database table name.
	 *
	 * @var string
	 */
	protected string $table;

	/**
	 * Create a new database lock instance.
	 */
	public function __construct(
		Connection $connection,
		string $table,
		string $name,
		int $seconds,
		string|null $owner = null,
		array $lottery = [2, 100],
		$defaultTimeoutInSeconds = 86400
	) {
		parent::__construct($name, $seconds, $owner);

		$this->connection = $connection;
		$this->table = $table;
		$this->lottery = $lottery;
		$this->defaultTimeoutInSeconds = $defaultTimeoutInSeconds;
	}

	/**
	 * {@inheritdoc}
	 */
	public function acquire(): bool
	{
		try {
			$this->connection->table($this->table)
				->insert([
					'key' => $this->name,
					'owner' => $this->owner,
					'expiration' => $this->expiresAt(),
				]);

			$acquired = true;
		} catch (QueryException) {
			$updated = $this->connection->table($this->table)
				->where('key', $this->name)
				->where(function ($query) {
					return $query->where('owner', $this->owner)->orWhere('expiration', '<=', $this->currentTime());
				})
				->update([
					'owner' => $this->owner,
					'expiration' => $this->expiresAt(),
				]);

			$acquired = $updated >= 1;
		}

		if (random_int(1, $this->lottery[1]) <= $this->lottery[0]) {
			$this->connection->table($this->table)
				->where('expiration', '<=', $this->currentTime())
				->delete();
		}

		return $acquired;
	}

	/**
	 * Get the UNIX timestamp indicating when the lock should expire.
	 */
	protected function expiresAt(): int
	{
		$lockTimeout = $this->seconds > 0 ? $this->seconds : $this->defaultTimeoutInSeconds;

		return $this->currentTime() + $lockTimeout;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forceRelease(): void
	{
		$this->connection->table($this->table)
			->where('key', $this->name)
			->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCurrentOwner()
	{
		return optional($this->connection->table($this->table)->where('key', $this->name)->first())->owner;
	}

	/**
	 * Get the name of the database connection being used to manage the lock.
	 */
	public function getConnectionName(): string
	{
		return $this->connection->getName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function release(): bool
	{
		if ($this->isOwnedByCurrentProcess()) {
			$this->connection->table($this->table)
				->where('key', $this->name)
				->where('owner', $this->owner)
				->delete();

			return true;
		}

		return false;
	}
}
