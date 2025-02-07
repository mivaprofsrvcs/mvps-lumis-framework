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
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class TrustHosts
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	/**
	 * The trusted hosts that have been configured to always be trusted.
	 *
	 * @var array<int, string>|(callable(): array<int, string>)|null
	 */
	protected static $alwaysTrust = null;

	/**
	 * Indicates whether subdomains of the application URL should be trusted.
	 *
	 * @var bool|null
	 */
	protected static bool|null $subdomains = null;

	/**
	 * Create a new trust hosts HTTP middleware instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get a regular expression matching the application URL and all of
	 * its subdomains.
	 */
	protected function allSubdomainsOfApplicationUrl(): string|null
	{
		$host = parse_url($this->app['config']->get('app.url'), PHP_URL_HOST);

		if ($host) {
			return '^(.+\.)?' . preg_quote($host) . '$';
		}

		return null;
	}

	/**
	 * Specify the hosts that should always be trusted.
	 */
	public static function at(array|callable $hosts, bool $subdomains = true): void
	{
		static::$alwaysTrust = $hosts;
		static::$subdomains = $subdomains;
	}

	/**
	 * Flush the state of the middleware.
	 */
	public static function flushState(): void
	{
		static::$alwaysTrust = null;
		static::$subdomains = null;
	}

	/**
	 * Handle the incoming request.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if ($this->shouldSpecifyTrustedHosts()) {
			Request::setTrustedHosts(array_filter($this->hosts()));
		}

		return $next($request);
	}

	/**
	 * Get the host patterns that should be trusted.
	 */
	public function hosts(): array
	{
		if (is_null(static::$alwaysTrust)) {
			return [$this->allSubdomainsOfApplicationUrl()];
		}

		$hosts = match (true) {
			is_array(static::$alwaysTrust) => static::$alwaysTrust,
			is_callable(static::$alwaysTrust) => call_user_func(static::$alwaysTrust),
			default => [],
		};

		if (static::$subdomains) {
			$hosts[] = $this->allSubdomainsOfApplicationUrl();
		}

		return $hosts;
	}

	/**
	 * Determine if the application should specify trusted hosts.
	 */
	protected function shouldSpecifyTrustedHosts(): bool
	{
		return ! $this->app->environment('local');
	}
}
