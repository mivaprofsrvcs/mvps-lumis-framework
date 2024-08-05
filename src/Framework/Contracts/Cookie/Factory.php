<?php

namespace MVPS\Lumis\Framework\Contracts\Cookie;

use Symfony\Component\HttpFoundation\Cookie;

interface Factory
{
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
	): Cookie;

	/**
	 * Expire the given cookie.
	 */
	public function forget(string $name, string|null $path = null, string|null $domain = null): Cookie;

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
	): Cookie;
}
