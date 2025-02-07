<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Cookie;

use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Contracts\Cookie\QueueingFactory as CookieJarContract;
use MVPS\Lumis\Framework\Support\Arr;
use Symfony\Component\HttpFoundation\Cookie;

class CookieJar implements CookieJarContract
{
	use InteractsWithTime;
	use Macroable;

	/**
	 * The default domain (if specified).
	 *
	 * @var string|null
	 */
	protected string|null $domain = null;

	/**
	 * The default path (if specified).
	 *
	 * @var string
	 */
	protected string $path = '/';

	/**
	 * All of the cookies queued for sending.
	 *
	 * @var array<\Symfony\Component\HttpFoundation\Cookie>
	 */
	protected array $queued = [];

	/**
	 * The default SameSite option (defaults to lax).
	 *
	 * @var string
	 */
	protected string $sameSite = 'lax';

	/**
	 * The default secure setting (defaults to null).
	 *
	 * @var bool|null
	 */
	protected bool|null $secure = null;

	/**
	 * Queue a cookie to expire with the next response.
	 */
	public function expire(string $name, string|null $path = null, string|null $domain = null): void
	{
		$this->queue($this->forget($name, $path, $domain));
	}

	/**
	 * Flush the cookies which have been queued for the next request.
	 */
	public function flushQueuedCookies(): static
	{
		$this->queued = [];

		return $this;
	}

	/**
	 * Create a cookie that lasts "forever" (five years).
	 */
	public function forever(
		string $name,
		string|null $value = null,
		string|null $path = null,
		string|null $domain = null,
		bool|null $secure = null,
		bool $httpOnly = true,
		bool $raw = false,
		string|null $sameSite = null
	): Cookie {
		return $this->make($name, $value, 576000, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
	}

	/**
	 * Expire the given cookie.
	 */
	public function forget(string $name, string|null $path = null, string|null $domain = null): Cookie
	{
		return $this->make($name, null, -2628000, $path, $domain);
	}

	/**
	 * Get the path and domain, or the default values.
	 */
	protected function getPathAndDomain(
		string $path,
		string|null $domain,
		bool|null $secure = null,
		string|null $sameSite = null
	): array {
		return [
			$path ?: $this->path,
			$domain ?: $this->domain,
			is_bool($secure) ? $secure : $this->secure,
			$sameSite ?: $this->sameSite,
		];
	}

	/**
	 * Get the cookies which have been queued for the next request.
	 */
	public function getQueuedCookies(): array
	{
		return Arr::flatten($this->queued);
	}

	/**
	 * Determine if a cookie has been queued.
	 */
	public function hasQueued(string $key, string|null $path = null): bool
	{
		return ! is_null($this->queued($key, null, $path));
	}

	/**
	 * Create a new cookie instance.
	 */
	public function make(
		string $name,
		string|null $value = null,
		int $minutes = 0,
		string|null $path = null,
		string|null $domain = null,
		bool|null $secure = null,
		bool $httpOnly = true,
		bool $raw = false,
		string|null $sameSite = null
	): Cookie {
		[$path, $domain, $secure, $sameSite] = $this->getPathAndDomain($path, $domain, $secure, $sameSite);

		$time = ($minutes === 0) ? 0 : $this->availableAt($minutes * 60);

		return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
	}

	/**
	 * Queue a cookie to send with the next response.
	 */
	public function queue(mixed ...$parameters): void
	{
		$cookie = isset($parameters[0]) && $parameters[0] instanceof Cookie
			? $cookie = $parameters[0]
			: $this->make(...array_values($parameters));

		if (! isset($this->queued[$cookie->getName()])) {
			$this->queued[$cookie->getName()] = [];
		}

		$this->queued[$cookie->getName()][$cookie->getPath()] = $cookie;
	}

	/**
	 * Get a queued cookie instance.
	 */
	public function queued(string $key, mixed $default = null, string|null $path = null): Cookie|null
	{
		$queued = Arr::get($this->queued, $key, $default);

		if (is_null($path)) {
			return Arr::last($queued, null, $default);
		}

		return Arr::get($queued, $path, $default);
	}

	/**
	 * Set the default path and domain for the jar.
	 */
	public function setDefaultPathAndDomain(
		string $path,
		string|null $domain = null,
		bool|null $secure = false,
		string|null $sameSite = null
	): static {
		[$this->path, $this->domain, $this->secure, $this->sameSite] = [$path, $domain, $secure, $sameSite];

		return $this;
	}

	/**
	 * Remove a cookie from the queue.
	 */
	public function unqueue(string $name, string|null $path = null): void
	{
		if (is_null($path)) {
			unset($this->queued[$name]);

			return;
		}

		unset($this->queued[$name][$path]);

		if (empty($this->queued[$name])) {
			unset($this->queued[$name]);
		}
	}
}
