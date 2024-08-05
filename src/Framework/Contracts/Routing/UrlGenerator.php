<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use DateInterval;
use DateTimeInterface;

interface UrlGenerator
{
	/**
	 * Get the URL to a controller action.
	 */
	public function action(string|array $action, mixed $parameters = [], bool $absolute = true): string;

	/**
	 * Generate the URL to an application asset.
	 */
	public function asset(string $path, bool|null $secure = null): string;

	/**
	 * Get the current URL for the request.
	 */
	public function current(): string;

	/**
	 * Get the root controller namespace.
	 */
	public function getRootControllerNamespace(): string;

	/**
	 * Get the URL for the previous request.
	 */
	public function previous(mixed $fallback = false): string;

	/**
	 * Get the URL to a named route.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function route(string $name, mixed $parameters = [], bool $absolute = true): string;

	/**
	 * Generate a secure, absolute URL to the given path.
	 */
	public function secure(string $path, array $parameters = []): string;

	/**
	 * Set the root controller namespace.
	 */
	public function setRootControllerNamespace(string $rootNamespace): static;

	/**
	 * Create a signed route URL for a named route.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function signedRoute(
		string $name,
		mixed $parameters = [],
		DateTimeInterface|DateInterval|int|null $expiration = null,
		bool $absolute = true
	): string;

	/**
	 * Create a temporary signed route URL for a named route.
	 */
	public function temporarySignedRoute(
		string $name,
		DateTimeInterface|DateInterval|int $expiration,
		array $parameters = [],
		bool $absolute = true
	): string;

	/**
	 * Generate an absolute URL to the given path.
	 */
	public function to(string $path, mixed $extra = [], bool|null $secure = null): string;
}
