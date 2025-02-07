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

namespace MVPS\Lumis\Framework\Routing;

use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Http\RedirectResponse;
use MVPS\Lumis\Framework\Session\Store as SessionStore;

class Redirector
{
	use Macroable;

	/**
	 * The URL generator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\UrlGenerator
	 */
	protected UrlGenerator $generator;

	/**
	 * The session store instance.
	 *
	 * @var \MVPS\Lumis\Framework\Session\Store|null
	 */
	protected SessionStore|null $session = null;

	/**
	 * Create a new redirector instance.
	 */
	public function __construct(UrlGenerator $generator)
	{
		$this->generator = $generator;
	}

	/**
	 * Create a new redirect response to a controller action.
	 */
	public function action(
		string|array $action,
		mixed $parameters = [],
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->to($this->generator->action($action, $parameters), $status, $headers);
	}

	/**
	 * Creates a redirect response to an external URL.
	 *
	 * Bypasses URL validation for external redirects.
	 */
	public function away(string $path, int $status = 302, array $headers = []): RedirectResponse
	{
		return $this->createRedirect($path, $status, $headers);
	}

	/**
	 * Create a new redirect response to the previous location.
	 */
	public function back(int $status = 302, array $headers = [], mixed $fallback = false): RedirectResponse
	{
		return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
	}

	/**
	 * Create a new redirect response.
	 */
	protected function createRedirect(string $path, int $status, array $headers): RedirectResponse
	{
		return tap(new RedirectResponse($path, $status, $headers), function ($redirect) {
			if (isset($this->session)) {
				$redirect->setSession($this->session);
			}

			$redirect->setRequest($this->generator->getRequest());
		});
	}


	/**
	 * Create a new redirect response to the previously intended location.
	 */
	public function intended(
		mixed $default = '/',
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): RedirectResponse {
		$path = $this->session->pull('url.intended', $default);

		return $this->to($path, $status, $headers, $secure);
	}

	/**
	 * Get the "intended" URL from the session.
	 */
	public function getIntendedUrl(): string|null
	{
		return $this->session->get('url.intended');
	}

	/**
	 * Get the URL generator instance.
	 */
	public function getUrlGenerator(): UrlGenerator
	{
		return $this->generator;
	}

	/**
	 * Create a new redirect response, while putting the
	 * current URL in the session.
	 */
	public function guest(
		string $path,
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): RedirectResponse {
		$request = $this->generator->getRequest();

		$intended = $request->isMethod('GET') && $request->route() && ! $request->expectsJson()
			? $this->generator->full()
			: $this->generator->previous();

		if ($intended) {
			$this->setIntendedUrl($intended);
		}

		return $this->to($path, $status, $headers, $secure);
	}

	/**
	 * Create a new redirect response to the current URI.
	 */
	public function refresh(int $status = 302, array $headers = []): RedirectResponse
	{
		return $this->to($this->generator->getRequest()->getPath(), $status, $headers);
	}

	/**
	 * Create a new redirect response to a named route.
	 */
	public function route(
		string $route,
		mixed $parameters = [],
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->to($this->generator->route($route, $parameters), $status, $headers);
	}

	/**
	 * Create a new redirect response to the given HTTPS path.
	 */
	public function secure(string $path, int $status = 302, array $headers = []): RedirectResponse
	{
		return $this->to($path, $status, $headers, true);
	}

	/**
	 * Set the "intended" URL in the session.
	 */
	public function setIntendedUrl(string $url): static
	{
		$this->session->put('url.intended', $url);

		return $this;
	}

	/**
	 * Set the active session store.
	 */
	public function setSession(SessionStore $session): void
	{
		$this->session = $session;
	}

	/**
	 * Create a new redirect response to a signed named route.
	 */
	public function signedRoute(
		string $route,
		mixed $parameters = [],
		DateTimeInterface|DateInterval|int|null $expiration = null,
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->to($this->generator->signedRoute($route, $parameters, $expiration), $status, $headers);
	}

	/**
	 * Create a new redirect response to a signed named route.
	 */
	public function temporarySignedRoute(
		string $route,
		DateTimeInterface|DateInterval|int|null $expiration,
		mixed $parameters = [],
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->to($this->generator->temporarySignedRoute($route, $expiration, $parameters), $status, $headers);
	}

	/**
	 * Create a new redirect response to the given path.
	 */
	public function to(string $path, int $status = 302, array $headers = [], bool|null $secure = null): RedirectResponse
	{
		return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
	}
}
