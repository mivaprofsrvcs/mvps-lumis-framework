<?php

namespace MVPS\Lumis\Framework\Configuration;

use ArrayAccess;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Contracts\Configuration\Repository as ConfigRepositoryContract;

class Repository implements ArrayAccess, ConfigRepositoryContract
{
	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected array $items;

	/**
	 * Create a new configuration repository.
	 */
	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/**
	 * Get all of the configuration items for the application.
	 */
	public function all(): array
	{
		return $this->items;
	}

	/**
	 * Get the specified configuration value.
	 */
	public function get(array|string $key, mixed $default = null): mixed
	{
		if (is_array($key)) {
			return $this->getMany($key);
		}

		return Arr::get($this->items, $key, $default);
	}

	/**
	 * Get many configuration values.
	 */
	public function getMany(array $keys): array
	{
		$config = [];

		foreach ($keys as $key => $default) {
			if (is_numeric($key)) {
				[$key, $default] = [$default, null];
			}

			$config[$key] = Arr::get($this->items, $key, $default);
		}

		return $config;
	}

	/**
	 * Determine if the given configuration value exists.
	 */
	public function has(string $key): bool
	{
		return Arr::has($this->items, $key);
	}

	/**
	 * Determine if the given configuration option exists.
	 */
	public function offsetExists(mixed $key): bool
	{
		return $this->has($key);
	}

	/**
	 * Get a configuration option.
	 */
	public function offsetGet(mixed $key): mixed
	{
		return $this->get($key);
	}

	/**
	 * Set a configuration option.
	 */
	public function offsetSet(mixed $key, mixed $value): void
	{
		$this->set($key, $value);
	}

	/**
	 * Unset a configuration option.
	 */
	public function offsetUnset(mixed $key): void
	{
		$this->set($key, null);
	}

	/**
	 * Prepend a value onto an array configuration value.
	 */
	public function prepend(string $key, mixed $value): void
	{
		$array = $this->get($key, []);

		array_unshift($array, $value);

		$this->set($key, $array);
	}

	/**
	 * Push a value onto an array configuration value.
	 */
	public function push(string $key, mixed $value): void
	{
		$array = $this->get($key, []);

		$array[] = $value;

		$this->set($key, $array);
	}

	/**
	 * Set a given configuration value.
	 */
	public function set(array|string $key, mixed $value = null): void
	{
		$keys = is_array($key) ? $key : [$key => $value];

		foreach ($keys as $key => $value) {
			Arr::set($this->items, $key, $value);
		}
	}
}
