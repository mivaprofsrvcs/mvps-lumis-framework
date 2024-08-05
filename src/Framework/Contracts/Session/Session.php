<?php

namespace MVPS\Lumis\Framework\Contracts\Session;

use MVPS\Lumis\Framework\Http\Request;
use SessionHandlerInterface;

interface Session
{
	/**
	 * Get all of the session data.
	 */
	public function all(): array;

	/**
	 * Checks if a key exists.
	 */
	public function exists(string|array $key): bool;

	/**
	 * Remove all of the items from the session.
	 */
	public function flush(): void;

	/**
	 * Remove one or many items from the session.
	 */
	public function forget(string|array $keys): void;

	/**
	 * Get an item from the session.
	 */
	public function get(string $key, mixed $default = null): mixed;

	/**
	 * Get the session handler instance.
	 */
	public function getHandler(): SessionHandlerInterface;

	/**
	 * Get the current session ID.
	 */
	public function getId(): string;

	/**
	 * Get the name of the session.
	 */
	public function getName(): string;

	/**
	 * Determine if the session handler needs a request.
	 */
	public function handlerNeedsRequest(): bool;

	/**
	 * Checks if a key is present and not null.
	 */
	public function has(string|array $key): bool;

	/**
	 * Flush the session data and regenerate the ID.
	 */
	public function invalidate(): bool;

	/**
	 * Determine if the session has been started.
	 */
	public function isStarted(): bool;

	/**
	 * Generate a new session ID for the session.
	 */
	public function migrate(bool $destroy = false): bool;

	/**
	 * Get the previous URL from the session.
	 */
	public function previousUrl(): string|null;

	/**
	 * Get the value of a given key and then forget it.
	 */
	public function pull(string $key, mixed $default = null): mixed;

	/**
	 * Put a key / value pair or array of key / value pairs in the session.
	 */
	public function put(string|array $key, mixed $value = null): void;

	/**
	 * Generate a new session identifier.
	 */
	public function regenerate(bool $destroy = false): bool;

	/**
	 * Regenerate the CSRF token value.
	 */
	public function regenerateToken(): void;

	/**
	 * Remove an item from the session, returning its value.
	 */
	public function remove(string $key): mixed;

	/**
	 * Save the session data to storage.
	 */
	public function save(): void;

	/**
	 * Set the session ID.
	 */
	public function setId(string $id): void;

	/**
	 * Set the name of the session.
	 */
	public function setName(string $name): void;

	/**
	 * Set the "previous" URL in the session.
	 */
	public function setPreviousUrl(string $url): void;

	/**
	 * Set the request on the handler instance.
	 */
	public function setRequestOnHandler(Request $request): void;

	/**
	 * Start the session, reading the data from a handler.
	 */
	public function start(): bool;

	/**
	 * Get the CSRF token value.
	 */
	public function token(): string;
}
