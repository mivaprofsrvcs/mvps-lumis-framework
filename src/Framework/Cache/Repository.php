<?php

namespace MVPS\Lumis\Framework\Cache;

use ArrayAccess;
use BadMethodCallException;
use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Cache\Events\CacheHit;
use MVPS\Lumis\Framework\Cache\Events\CacheMissed;
use MVPS\Lumis\Framework\Cache\Events\ForgettingKey;
use MVPS\Lumis\Framework\Cache\Events\KeyForgetFailed;
use MVPS\Lumis\Framework\Cache\Events\KeyForgotten;
use MVPS\Lumis\Framework\Cache\Events\KeyWriteFailed;
use MVPS\Lumis\Framework\Cache\Events\KeyWritten;
use MVPS\Lumis\Framework\Cache\Events\RetrievingKey;
use MVPS\Lumis\Framework\Cache\Events\RetrievingManyKeys;
use MVPS\Lumis\Framework\Cache\Events\WritingKey;
use MVPS\Lumis\Framework\Cache\Events\WritingManyKeys;
use MVPS\Lumis\Framework\Contracts\Cache\Repository as CacheContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;

class Repository implements ArrayAccess, CacheContract
{
	use InteractsWithTime;
	use Macroable {
		__call as macroCall;
	}

	/**
	 * The cache store configuration options.
	 *
	 * @var array
	 */
	protected array $config = [];

	/**
	 * The default number of seconds to store items.
	 *
	 * @var int|null
	 */
	protected int|null $default = 3600;

	/**
	 * The event dispatcher implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher|null
	 */
	protected Dispatcher|null $events = null;

	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Contracts\Cache\Store
	 */
	protected Store $store;

	/**
	 * Create a new cache repository instance.
	 */
	public function __construct(Store $store, array $config = [])
	{
		$this->store = $store;
		$this->config = $config;
	}

	/**
	 * {@inheritdoc}
	 */
	public function add($key, $value, $ttl = null)
	{
		$seconds = null;

		if (! is_null($ttl)) {
			$seconds = $this->getSeconds($ttl);

			if ($seconds <= 0) {
				return false;
			}

			// If the cache store provides an "add" method, we'll delegate to
			// it, allowing the store to handle this operation directly.
			// Certain cache drivers can implement this operation more
			// efficiently with full atomicity, ensuring the best performance
			// and accuracy for this specific logic.
			if (method_exists($this->store, 'add')) {
				return $this->store->add(
					$this->itemKey($key),
					$value,
					$seconds
				);
			}
		}

		// If the value isn't found in the cache, we'll store it so that future
		// requests can access it. We'll return true to indicate that the value
		// was successfully added. If the value already exists, we'll return
		// false to signal that no addition took place.
		if (is_null($this->get($key))) {
			return $this->put($key, $value, $seconds);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear(): bool
	{
		return $this->store->flush();
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return $this->store->decrement($key, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($key): bool
	{
		return $this->forget($key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteMultiple($keys): bool
	{
		$result = true;

		foreach ($keys as $key) {
			if (! $this->forget($key)) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Fire an event for this cache instance.
	 */
	protected function event(object|string $event): void
	{
		$this->events?->dispatch($event);
	}

	/**
	 * Retrieve an item from the cache by key, refreshing it in the background
	 * if it is stale.
	 */
	public function flexible(string $key, array $ttl, callable $callback, array|null $lock = null): mixed
	{
		[$key => $value, "{$key}:created" => $created] = $this->many([$key, "{$key}:created"]);

		if (is_null($created)) {
			return tap(
				value($callback),
				fn ($value) => $this->putMany(
					[
						$key => $value,
						"{$key}:created" => Carbon::now()->getTimestamp(),
					],
					$ttl[1]
				)
			);
		}

		if (($created + $this->getSeconds($ttl[0])) > Carbon::now()->getTimestamp()) {
			return $value;
		}

		$refresh = function () use ($key, $ttl, $callback, $lock, $created) {
			$this->store
				->lock(
					"lumis:cache:refresh:lock:{$key}",
					$lock['seconds'] ?? 0,
					$lock['owner'] ?? null
				)
				->get(function () use ($key, $callback, $created, $ttl) {
					if ($created !== $this->get("{$key}:created")) {
						return;
					}

					$this->putMany(
						[
							$key => value($callback),
							"{$key}:created" => Carbon::now()->getTimestamp(),
						],
						$ttl[1]
					);
				});
		};

		if (function_exists('defer')) {
			defer($refresh, "lumis:cache:refresh:{$key}");
		} else {
			$refresh();
		}

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forever($key, $value)
	{
		$this->event(new WritingKey($this->getName(), $key, $value));

		$result = $this->store->forever($this->itemKey($key), $value);

		if ($result) {
			$this->event(new KeyWritten($this->getName(), $key, $value));
		} else {
			$this->event(new KeyWriteFailed($this->getName(), $key, $value));
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forget($key)
	{
		$this->event(new ForgettingKey($this->getName(), $key));

		return tap(
			$this->store->forget($this->itemKey($key)),
			fn ($result) => $result
				? $this->event(new KeyForgotten($this->getName(), $key))
				: $this->event(new KeyForgetFailed($this->getName(), $key))
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key, $default = null): mixed
	{
		if (is_array($key)) {
			return $this->many($key);
		}

		$this->event(new RetrievingKey($this->getName(), $key));

		$value = $this->store->get($this->itemKey($key));

		// If the cache value is not found, trigger the "cache missed" event and
		// retrieve the default value. The default value might be a callback, so
		// we will execute  the callback function to resolve and return the
		// appropriate value if necessary.
		if (is_null($value)) {
			$this->event(new CacheMissed($this->getName(), $key));

			$value = value($default);
		} else {
			$this->event(new CacheHit($this->getName(), $key, $value));
		}

		return $value;
	}

	/**
	 * Get the default cache time.
	 */
	public function getDefaultCacheTime(): int|null
	{
		return $this->default;
	}

	/**
	 * Get the event dispatcher instance.
	 */
	public function getEventDispatcher(): Dispatcher|null
	{
		return $this->events;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMultiple($keys, $default = null): iterable
	{
		$defaults = [];

		foreach ($keys as $key) {
			$defaults[$key] = $default;
		}

		return $this->many($defaults);
	}

	/**
	 * Get the name of the cache store.
	 */
	protected function getName(): string|null
	{
		return $this->config['store'] ?? null;
	}

	/**
	 * Calculate the number of seconds for the given TTL.
	 */
	protected function getSeconds(DateTimeInterface|DateInterval|int $ttl): int
	{
		$duration = $this->parseDateInterval($ttl);

		if ($duration instanceof DateTimeInterface) {
			$duration = Carbon::now()->diffInSeconds($duration, false);
		}

		return (int) ($duration > 0 ? $duration : 0);
	}

	/**
	 * Get the cache store implementation.
	 */
	public function getStore(): Store
	{
		return $this->store;
	}

	/**
	 * Handle a result for the "many" method.
	 */
	protected function handleManyResult(array $keys, string $key, mixed $value): mixed
	{
		// If the cache value is not found, we'll trigger a "cache missed"
		// event and retrieve the default value. The default value might be a
		// callback, so we'll ensure it gets executed, resolving to the final
		// value if necessary.
		if (is_null($value)) {
			$this->event(new CacheMissed($this->getName(), $key));

			return isset($keys[$key]) && ! array_is_list($keys)
				? value($keys[$key])
				: null;
		}

		// If a valid value is found, we'll trigger a "hit" event and return the
		// cached value. This event allows developers to hook into every cache
		// "hit" that occurs within the application, offering an opportunity to
		// react to successful cache retrievals.
		$this->event(new CacheHit($this->getName(), $key, $value));

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($key): bool
	{
		return ! is_null($this->get($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment($key, $value = 1)
	{
		return $this->store->increment($key, $value);
	}

	/**
	 * Format the key for a cache item.
	 */
	protected function itemKey(string $key): string
	{
		return $key;
	}

	/**
	 * Retrieve multiple items from the cache by key.
	 *
	 * Items not found in the cache will have a null value.
	 */
	public function many(array $keys): array
	{
		$this->event(new RetrievingManyKeys($this->getName(), $keys));

		$values = $this->store->many(
			collection($keys)
				->map(fn ($value, $key) => is_string($key) ? $key : $value)
				->values()
				->all()
		);

		return collection($values)
			->map(fn ($value, $key) => $this->handleManyResult($keys, $key, $value))
			->all();
	}

	/**
	 * Determine if an item doesn't exist in the cache.
	 */
	public function missing(string $key): bool
	{
		return ! $this->has($key);
	}

	/**
	 * Determine if a cached value exists.
	 */
	public function offsetExists(mixed $key): bool
	{
		return $this->has($key);
	}

	/**
	 * Retrieve an item from the cache by key.
	 */
	public function offsetGet(mixed $key): mixed
	{
		return $this->get($key);
	}

	/**
	 * Store an item in the cache for the default time.
	 */
	public function offsetSet(mixed $key, mixed $value): void
	{
		$this->put($key, $value, $this->default);
	}

	/**
	 * Remove an item from the cache.
	 */
	public function offsetUnset(mixed $key): void
	{
		$this->forget($key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function pull($key, $default = null)
	{
		return tap($this->get($key, $default), fn () => $this->forget($key));
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $ttl = null)
	{
		if (is_array($key)) {
			return $this->putMany($key, $value);
		}

		if (is_null($ttl)) {
			return $this->forever($key, $value);
		}

		$seconds = $this->getSeconds($ttl);

		if ($seconds <= 0) {
			return $this->forget($key);
		}

		$this->event(new WritingKey($this->getName(), $key, $value, $seconds));

		$result = $this->store->put($this->itemKey($key), $value, $seconds);

		if ($result) {
			$this->event(new KeyWritten($this->getName(), $key, $value, $seconds));
		} else {
			$this->event(new KeyWriteFailed($this->getName(), $key, $value, $seconds));
		}

		return $result;
	}

	/**
	 * Store multiple items in the cache for a given number of seconds.
	 *
	 * @param  array  $values
	 * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
	 * @return bool
	 */
	public function putMany(array $values, $ttl = null): bool
	{
		if (is_null($ttl)) {
			return $this->putManyForever($values);
		}

		$seconds = $this->getSeconds($ttl);

		if ($seconds <= 0) {
			return $this->deleteMultiple(array_keys($values));
		}

		$this->event(new WritingManyKeys(
			$this->getName(),
			array_keys($values),
			array_values($values),
			$seconds
		));

		$result = $this->store->putMany($values, $seconds);

		foreach ($values as $key => $value) {
			if ($result) {
				$this->event(new KeyWritten($this->getName(), $key, $value, $seconds));
			} else {
				$this->event(new KeyWriteFailed($this->getName(), $key, $value, $seconds));
			}
		}

		return $result;
	}

	/**
	 * Store multiple items in the cache indefinitely.
	 */
	protected function putManyForever(array $values): bool
	{
		$result = true;

		foreach ($values as $key => $value) {
			if (! $this->forever($key, $value)) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function remember($key, $ttl, Closure $callback)
	{
		$value = $this->get($key);

		// If the item is already present in the cache, we can return it right
		// away. Otherwise, we'll execute the provided Closure, store the result
		// in the cache for the specified duration, and make it available for
		// future requests.
		if (! is_null($value)) {
			return $value;
		}

		$value = $callback();

		$this->put($key, $value, value($ttl, $value));

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rememberForever($key, Closure $callback)
	{
		$value = $this->get($key);

		// If the item is already present in the cache, we can return it right
		// away. Otherwise, we'll execute the provided Closure, store the result
		// in the cache forever so it is available for all subsequent requests.
		if (! is_null($value)) {
			return $value;
		}

		$this->forever($key, $value = $callback());

		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sear($key, Closure $callback)
	{
		return $this->rememberForever($key, $callback);
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($key, $value, $ttl = null): bool
	{
		return $this->put($key, $value, $ttl);
	}

	/**
	 * Set the default cache time in seconds.
	 */
	public function setDefaultCacheTime(int|null $seconds): static
	{
		$this->default = $seconds;

		return $this;
	}

	/**
	 * Set the event dispatcher instance.
	 */
	public function setEventDispatcher(Dispatcher $events): void
	{
		$this->events = $events;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setMultiple($values, $ttl = null): bool
	{
		return $this->putMany(is_array($values)
			? $values
			: iterator_to_array($values), $ttl);
	}

	/**
	 * Set the cache store implementation.
	 */
	public function setStore(Store $store): static
	{
		$this->store = $store;

		return $this;
	}

	/**
	 * Determine if the current store supports tags.
	 */
	public function supportsTags(): bool
	{
		return method_exists($this->store, 'tags');
	}

	/**
	 * Begin executing a new tags operation if the store supports it.
	 *
	 * @throws \BadMethodCallException
	 */
	public function tags(mixed $names): TaggedCache
	{
		if (! $this->supportsTags()) {
			throw new BadMethodCallException('This cache store does not support tagging.');
		}

		$cache = $this->store->tags(is_array($names) ? $names : func_get_args());

		$cache->config = $this->config;

		if (! is_null($this->events)) {
			$cache->setEventDispatcher($this->events);
		}

		return $cache->setDefaultCacheTime($this->default);
	}

	/**
	 * Handle dynamic calls into macros or pass missing methods to the store.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		return $this->store->$method(...$parameters);
	}

	/**
	 * Clone cache repository instance.
	 */
	public function __clone(): void
	{
		$this->store = clone $this->store;
	}
}
