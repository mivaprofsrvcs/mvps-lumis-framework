<?php

namespace MVPS\Lumis\Framework\Cache;

use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Cache\DatabaseLock;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;

class DatabaseStore implements LockProvider, Store
{
	use InteractsWithTime;

	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\ConnectionInterface
	 */
	protected ConnectionInterface $connection;

	/**
	 * The default number of seconds that a lock should be held.
	 *
	 * @var int
	 */
	protected int $defaultLockTimeoutInSeconds;

	/**
	 * The database connection instance that should be used to manage locks.
	 *
	 * @var \Illuminate\Database\ConnectionInterface|null
	 */
	protected ConnectionInterface|null $lockConnection = null;

	/**
	 * An array representation of the lock lottery odds.
	 *
	 * @var array
	 */
	protected array $lockLottery;

	/**
	 * The name of the cache locks table.
	 *
	 * @var string
	 */
	protected string $lockTable;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * The name of the cache table.
	 *
	 * @var string
	 */
	protected string $table;

	/**
	 * Create a new database store.
	 */
	public function __construct(
		ConnectionInterface $connection,
		string $table,
		string $prefix = '',
		string $lockTable = 'cache_locks',
		array $lockLottery = [2, 100],
		int $defaultLockTimeoutInSeconds = 86400
	) {
		$this->table = $table;
		$this->prefix = $prefix;
		$this->connection = $connection;
		$this->lockTable = $lockTable;
		$this->lockLottery = $lockLottery;
		$this->defaultLockTimeoutInSeconds = $defaultLockTimeoutInSeconds;
	}

	/**
	 * {@inheritdoc}
	 */
	public function add($key, $value, $seconds)
	{
		if (! is_null($this->get($key))) {
			return false;
		}

		$key = $this->prefix . $key;
		$value = $this->serialize($value);
		$expiration = $this->getTime() + $seconds;

		if (! $this->getConnection() instanceof SqlServerConnection) {
			return $this->table()->insertOrIgnore(compact('key', 'value', 'expiration')) > 0;
		}

		try {
			return $this->table()->insert(compact('key', 'value', 'expiration'));
		} catch (QueryException $e) {
			// ...
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return $this->incrementOrDecrement(
			$key,
			$value,
			fn ($current, $value) => $current - $value
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush()
	{
		$this->table()->delete();

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forever($key, $value)
	{
		return $this->put($key, $value, 315360000);
	}

	/**
	 * {@inheritdoc}
	 */
	public function forget($key)
	{
		return $this->forgetMany([$key]);
	}

	/**
	 * Remove an item from the cache if it is expired.
	 */
	public function forgetIfExpired(string $key): bool
	{
		return $this->forgetManyIfExpired([$key]);
	}

	/**
	 * Remove all items from the cache.
	 */
	protected function forgetMany(array $keys): bool
	{
		$this->table()
			->whereIn('key', array_map(fn ($key) => $this->prefix . $key, $keys))
			->delete();

		return true;
	}

	/**
	 * Remove all expired items from the given set from the cache.
	 */
	protected function forgetManyIfExpired(array $keys, bool $prefixed = false): bool
	{
		$this->table()
			->whereIn(
				'key',
				$prefixed ? $keys : array_map(fn ($key) => $this->prefix . $key, $keys)
			)
			->where('expiration', '<=', $this->getTime())
			->delete();

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		return $this->many([$key])[$key];
	}

	/**
	 * Get the underlying database connection.
	 */
	public function getConnection(): ConnectionInterface
	{
		return $this->connection;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPrefix(): string
	{
		return $this->prefix;
	}

	/**
	 * Get the current system time.
	 */
	protected function getTime(): int
	{
		return $this->currentTime();
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment($key, $value = 1)
	{
		return $this->incrementOrDecrement(
			$key,
			$value,
			fn ($current, $value) => $current + $value
		);
	}

	/**
	 * Increment or decrement an item in the cache.
	 */
	protected function incrementOrDecrement(string $key, int|float $value, Closure $callback): int|false
	{
		return $this->connection->transaction(function () use ($key, $value, $callback) {
			$prefixed = $this->prefix . $key;

			$cache = $this->table()
				->where('key', $prefixed)
				->lockForUpdate()
				->first();

			// If no value is found in the cache, we'll simply return false.
			// However, if a value exists, it will be decrypted, and the
			// function will continue to process either an increment or
			// decrement operation, depending on the provided action callbacks.
			if (is_null($cache)) {
				return false;
			}

			$cache = is_array($cache) ? (object) $cache : $cache;

			$current = $this->unserialize($cache->value);

			// We invoke the provided callback to handle the increment or
			// decrement operation. Using a callback streamlines the process,
			// allowing us to centralize this logic rather than duplicating it
			// across multiple methods, ensuring consistency and efficiency.
			$new = $callback((int) $current, $value);

			if (! is_numeric($current)) {
				return false;
			}

			// We are updating the cache entry in the table, ensuring that the
			// value is securely encrypted. Since Lumis encrypts all database
			// cache values by default, this adds an extra layer of security,
			// preventing unauthorized access. Once the value is stored, we will
			// return it for further use.
			$this->table()
				->where('key', $prefixed)
				->update(['value' => $this->serialize($new)]);

			return $new;
		});
	}

	/**
	 * {@inheritdoc}
	 */
	public function lock($name, $seconds = 0, $owner = null)
	{
		return new DatabaseLock(
			$this->lockConnection ?? $this->connection,
			$this->lockTable,
			$this->prefix . $name,
			$seconds,
			$owner,
			$this->lockLottery,
			$this->defaultLockTimeoutInSeconds
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function many(array $keys)
	{
		if (count($keys) === 0) {
			return [];
		}

		$results = array_fill_keys($keys, null);

		// First, we'll fetch all items from the cache using their corresponding
		// keys along with the specified prefix. After retrieving the items,
		// we'll loop through them and convert any that are currently in array
		// format into objects for easier manipulation and consistency in the
		// application.
		$values = $this->table()
			->whereIn('key', array_map(fn ($key) => $this->prefix . $key, $keys))
			->get()
			->map(fn ($value) => is_array($value) ? (object) $value : $value);

		$currentTime = $this->currentTime();

		// If the cache expiration date has passed, we will remove the item from
		// the cache and return null, indicating that the cache entry is
		// expired. We'll leverage Carbon for comparing the expiration timestamp
		// with the stored column value.
		[$values, $expired] = $values->partition(fn ($cache) => $cache->expiration > $currentTime);

		if ($expired->isNotEmpty()) {
			$this->forgetManyIfExpired($expired->pluck('key')->all(), prefixed: true);
		}

		return Arr::map($results, function ($value, $key) use ($values) {
			$cache = $values->firstWhere('key', $this->prefix . $key);

			return $cache ? $this->unserialize($cache->value) : $value;
		});
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $seconds)
	{
		return $this->putMany([$key => $value], $seconds);
	}

	/**
	 * {@inheritdoc}
	 */
	public function putMany(array $values, $seconds)
	{
		$serializedValues = [];

		$expiration = $this->getTime() + $seconds;

		foreach ($values as $key => $value) {
			$serializedValues[] = [
				'key' => $this->prefix . $key,
				'value' => $this->serialize($value),
				'expiration' => $expiration,
			];
		}

		return $this->table()->upsert($serializedValues, 'key') > 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function restoreLock($name, $owner)
	{
		return $this->lock($name, 0, $owner);
	}

	/**
	 * Serialize the given value.
	 */
	protected function serialize(mixed $value): string
	{
		$result = serialize($value);

		if ($this->connection instanceof PostgresConnection && str_contains($result, "\0")) {
			$result = base64_encode($result);
		}

		return $result;
	}

	/**
	 * Specify the name of the connection that should be used to manage locks.
	 */
	public function setLockConnection(ConnectionInterface $connection): static
	{
		$this->lockConnection = $connection;

		return $this;
	}

	/**
	 * Set the cache key prefix.
	 */
	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	/**
	 * Get a query builder for the cache table.
	 */
	protected function table(): Builder
	{
		return $this->connection->table($this->table);
	}

	/**
	 * Unserialize the given value.
	 */
	protected function unserialize(string $value): mixed
	{
		if ($this->connection instanceof PostgresConnection && ! Str::contains($value, [':', ';'])) {
			$value = base64_decode($value);
		}

		return unserialize($value);
	}
}
