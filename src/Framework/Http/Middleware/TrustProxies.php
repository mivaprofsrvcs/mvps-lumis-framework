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

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Http\Request;

class TrustProxies
{
	/**
	 * The proxies headers that have been configured to always be trusted.
	 *
	 * @var int|null
	 */
	protected static int|null $alwaysTrustHeaders = null;

	/**
	 * The proxies that have been configured to always be trusted.
	 *
	 * @var array<int, string>|string|null
	 */
	protected static array|string|null $alwaysTrustProxies = null;

	/**
	 * The trusted proxies headers for the application.
	 *
	 * @var int
	 */
	protected int $headers = Request::HEADER_X_FORWARDED_FOR |
		Request::HEADER_X_FORWARDED_HOST |
		Request::HEADER_X_FORWARDED_PORT |
		Request::HEADER_X_FORWARDED_PROTO |
		Request::HEADER_X_FORWARDED_AWS_ELB;

		/**
	 * The trusted proxies for the application.
	 *
	 * @var array<int, string>|string|null
	 */
	protected array|string|null $proxies = null;

	/**
	 * Specify the IP addresses of proxies that should always be trusted.
	 */
	public static function at(array|string $proxies): void
	{
		static::$alwaysTrustProxies = $proxies;
	}

	/**
	 * Flush the state of the middleware.
	 */
	public static function flushState(): void
	{
		static::$alwaysTrustHeaders = null;
		static::$alwaysTrustProxies = null;
	}

	/**
	 * Retrieve trusted header name(s), falling back to defaults if config not set.
	 */
	protected function getTrustedHeaderNames(): int
	{
		$headers = $this->headers();

		if (is_int($headers)) {
			return $headers;
		}

		return match ($headers) {
			'HEADER_X_FORWARDED_AWS_ELB' => Request::HEADER_X_FORWARDED_AWS_ELB,
			'HEADER_FORWARDED' => Request::HEADER_FORWARDED,
			'HEADER_X_FORWARDED_FOR' => Request::HEADER_X_FORWARDED_FOR,
			'HEADER_X_FORWARDED_HOST' => Request::HEADER_X_FORWARDED_HOST,
			'HEADER_X_FORWARDED_PORT' => Request::HEADER_X_FORWARDED_PORT,
			'HEADER_X_FORWARDED_PROTO' => Request::HEADER_X_FORWARDED_PROTO,
			'HEADER_X_FORWARDED_PREFIX' => Request::HEADER_X_FORWARDED_PREFIX,
			default =>
				Request::HEADER_X_FORWARDED_FOR |
				Request::HEADER_X_FORWARDED_HOST |
				Request::HEADER_X_FORWARDED_PORT |
				Request::HEADER_X_FORWARDED_PROTO |
				Request::HEADER_X_FORWARDED_PREFIX |
				Request::HEADER_X_FORWARDED_AWS_ELB,
		};
	}

	/**
	 * Handle an incoming request.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\Http\HttpException
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		$request::setTrustedProxies([], $this->getTrustedHeaderNames());

		$this->setTrustedProxyIpAddresses($request);

		return $next($request);
	}

	/**
	 * Get the trusted headers.
	 */
	protected function headers(): int
	{
		return static::$alwaysTrustHeaders ?: $this->headers;
	}

	/**
	 * Get the trusted proxies.
	 */
	protected function proxies(): array|string|null
	{
		return static::$alwaysTrustProxies ?: $this->proxies;
	}

	/**
	 * Sets the trusted proxies on the request.
	 */
	protected function setTrustedProxyIpAddresses(Request $request): void
	{
		$trustedIps = $this->proxies() ?: config('trustedproxy.proxies');

		if ($trustedIps === '*' || $trustedIps === '**') {
			$this->setTrustedProxyIpAddressesToTheCallingIp($request);
		} else {
			$trustedIps = is_string($trustedIps)
				? array_map('trim', explode(',', $trustedIps))
				: $trustedIps;

			if (is_array($trustedIps)) {
				$this->setTrustedProxyIpAddressesToSpecificIps($request, $trustedIps);
			}
		}
	}

	/**
	 * Specify the IP addresses to trust explicitly.
	 */
	protected function setTrustedProxyIpAddressesToSpecificIps(Request $request, array $trustedIps): void
	{
		$request->setTrustedProxies($trustedIps, $this->getTrustedHeaderNames());
	}

	/**
	 * Set the trusted proxy to be the IP address calling this servers.
	 */
	protected function setTrustedProxyIpAddressesToTheCallingIp(Request $request): void
	{
		$request->setTrustedProxies(
			[$request->serverBag->get('REMOTE_ADDR')],
			$this->getTrustedHeaderNames()
		);
	}

	/**
	 * Specify the proxy headers that should always be trusted.
	 */
	public static function withHeaders(int $headers): void
	{
		static::$alwaysTrustHeaders = $headers;
	}
}
