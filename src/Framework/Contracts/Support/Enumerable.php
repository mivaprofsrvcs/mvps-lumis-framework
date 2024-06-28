<?php

namespace MVPS\Lumis\Framework\Contracts\Support;

use Countable;
use JsonSerializable;
use IteratorAggregate;

// TODO: implement all methods
interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
	/**
	 * Get all items in the enumerable.
	 */
	public function all(): array;

	/**
	 * Chunk the collection into chunks of the given size.
	 */
	public function chunk(int $size): static;

	/**
	 * Determine if an item exists in the enumerable.
	 */
	public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool;

	/**
	 * Count the number of items in the collection.
	 */
	public function count(): int;

	/**
	 * Dump the collection and end the script.
	 */
	public function dd(mixed ...$args): never;

	/**
	 * Dump the collection.
	 */
	public function dump(mixed ...$args): static;

	/**
	 * Determine if all items pass the given truth test.
	 */
	public function every(mixed $key, mixed $operator = null, mixed $value = null): mixed;

	/**
	 * Run a filter over each of the items.
	 */
	public function filter(callable|null $callback = null): static;

	/**
	 * Get the first item from the enumerable passing the given truth test.
	 */
	public function first(callable|null $callback = null, mixed $default = null): mixed;

	/**
	 * Get the first item by the given key value pair.
	 */
	public function firstWhere(string $key, mixed $operator = null, mixed $value = null): mixed;

	/**
	 * Get an item from the collection by key.
	 */
	public function get(mixed $key, mixed $default = null): mixed;

	/**
	 * Determine if an item exists in the collection by key.
	 */
	public function has(string $key): bool;

	/**
	 * Determine if any of the keys exist in the collection.
	 */
	public function hasAny(mixed $key): bool;

	/**
	 * Determine if the collection is empty or not.
	 */
	public function isEmpty(): bool;

	/**
	 * Determine if the collection is not empty.
	 */
	public function isNotEmpty(): bool;

	/**
	 * Convert the object into something JSON serializable.
	 */
	public function jsonSerialize(): mixed;

	/**
	 * Create a new collection instance if the value isn't one already.
	 */
	public static function make(Arrayable|iterable|null $items = []): static;

	/**
	 * Run a map over each of the items.
	 */
	public function map(callable $callback): static;

	/**
	 * Partition the collection into two arrays using the given callback or key.
	 */
	public function partition(mixed $key, mixed $operator = null, mixed $value = null): mixed;

	/**
	 * Get the values of a given key.
	 */
	public function pluck(string|array $value, string|null $key = null): static;

	/**
	 * Get a slice of items from the enumerable.
	 */
	public function slice(int $offset, int|null $length = null): static;

	/**
	 * Get the collection of items as a plain array.
	 */
	public function toArray(): array;

	/**
	 * Get the collection of items as JSON.
	 */
	public function toJson(int $options = 0): string;

	/**
	 * Reset the keys on the underlying array.
	 */
	public function values(): static;

	/**
	 * Filter items by the given key value pair.
	 */
	public function where(string $key, mixed $operator = null, mixed $value = null): static;

	/**
	 * Dynamically access collection proxies.
	 *
	 * @throws \Exception
	 */
	public function __get(string $key): mixed;

	/**
	 * Convert the collection to its string representation.
	 */
	public function __toString(): string;
}
