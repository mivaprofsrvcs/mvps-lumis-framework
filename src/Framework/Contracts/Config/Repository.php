<?php

namespace MVPS\Lumis\Framework\Contracts\Config;

interface Repository
{
	/**
	 * Get all of the configuration items for the application.
	 */
	public function all(): array;

	/**
	 * Get the specified configuration value.
	 */
	public function get(array|string $key, mixed $default = null): mixed;

	/**
	 * Determine if the given configuration value exists.
	 */
	public function has(string $key): bool;

	/**
	 * Prepend a value onto an array configuration value.
	 */
	public function prepend(string $key, mixed $value): void;

	/**
	 * Push a value onto an array configuration value.
	 */
	public function push(string $key, mixed $value): void;

	/**
	 * Set a given configuration value.
	 */
	public function set(array|string $key, mixed $value = null): void;
}
