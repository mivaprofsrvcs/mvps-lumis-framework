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

namespace MVPS\Lumis\Framework\Http;

use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Session\Store as SessionStore;
use MVPS\Lumis\Framework\Support\Str;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class RedirectResponse extends Response
{
	use ForwardsCalls;

	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request|null $request = null;

	/**
	 * The session store instance.
	 *
	 * @var \MVPS\Lumis\Framework\Session\Store|null
	 */
	protected SessionStore|null $session = null;

	/**
	 * The target URL for the redirection.
	 *
	 * @var string
	 */
	protected string $targetUrl;

	/**
	 * Create a new redirect HTTP response instance.
	 */
	public function __construct(string|UriInterface $url, int $status = 302, array $headers = [])
	{
		parent::__construct('', $status, $headers);

		$this->setTargetUrl($url);

		if (! $this->isRedirect()) {
			throw new InvalidArgumentException(
				sprintf('Invalid HTTP status code for redirect response: %d', $status, $status)
			);
		}
	}

	/**
	 * Flash an array of input to the session.
	 */
	public function exceptInput(): static
	{
		return $this->withInput($this->request->except(func_get_args()));
	}

	/**
	 * Get the original response content.
	 */
	public function getOriginalContent(): mixed
	{
		return null;
	}

	/**
	 * Get the request instance.
	 */
	public function getRequest(): Request|null
	{
		return $this->request;
	}

	/**
	 * Get the session store instance.
	 */
	public function getSession(): SessionStore|null
	{
		return $this->session;
	}

	/**
	 * Retrieves the target URL for the redirection.
	 */
	public function getTargetUrl(): string
	{
		return $this->targetUrl;
	}

	/**
	 * Flash an array of input to the session.
	 */
	public function onlyInput(): static
	{
		return $this->withInput($this->request->only(func_get_args()));
	}

	/**
	 * Parse the given errors into an appropriate value.
	 */
	protected function parseErrors(MessageProvider|array|string $provider): MessageBag
	{
		if ($provider instanceof MessageProvider) {
			return $provider->getMessageBag();
		}

		return new MessageBag((array) $provider);
	}

	/**
	 * Remove all uploaded files form the given input array.
	 */
	protected function removeFilesFromInput(array $input): array
	{
		foreach ($input as $key => $value) {
			if (is_array($value)) {
				$input[$key] = $this->removeFilesFromInput($value);
			}

			if ($value instanceof SymfonyUploadedFile) {
				unset($input[$key]);
			}
		}

		return $input;
	}

	/**
	 * Set the request instance.
	 */
	public function setRequest(Request $request): static
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Set the session store instance.
	 */
	public function setSession(SessionStore $session): static
	{
		$this->session = $session;

		return $this;
	}

	/**
	 * Sets the target URL for the redirection response.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setTargetUrl(string|UriInterface $url): static
	{
		$targetUrl = (string) $url;

		if ($targetUrl === '') {
			throw new InvalidArgumentException('Cannot redirect to an empty URL.');
		}

		$this->targetUrl = $targetUrl;

		$this->setContent(sprintf(
			'<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="refresh" content="0;url=\'%1$s\'" />
		<title>Redirecting to %1$s</title>
	</head>
	<body>
		Redirecting to <a href="%1$s">%1$s</a>.
	</body>
</html>',
			htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8')
		));

		$this->headerBag->set('Location', $targetUrl);

		$this->headerBag->set('Content-Type', 'text/html; charset=utf-8');

		return $this;
	}

	/**
	 * Flashes data to the session.
	 */
	public function with(string|array $key, mixed $value = null): static
	{
		$key = is_array($key) ? $key : [$key => $value];

		foreach ($key as $k => $v) {
			$this->session->flash($k, $v);
		}

		return $this;
	}

	/**
	 * Add cookies to the response.
	 */
	public function withCookies(array $cookies): static
	{
		foreach ($cookies as $cookie) {
			$this->headerBag->setCookie($cookie);
		}

		return $this;
	}

	/**
	 * Flash a container of errors to the session.
	 */
	public function withErrors(MessageProvider|array|string $provider, string $key = 'default'): static
	{
		$value = $this->parseErrors($provider);

		$errors = $this->session->get('errors', new ViewErrorBag);

		if (! $errors instanceof ViewErrorBag) {
			$errors = new ViewErrorBag;
		}

		$this->session->flash('errors', $errors->put($key, $value));

		return $this;
	}

	/**
	 * Add a fragment identifier to the URL.
	 */
	public function withFragment(string $fragment): static
	{
		return $this->withoutFragment()
			->setTargetUrl($this->getTargetUrl() . '#' . Str::after($fragment, '#'));
	}

	/**
	 * Flash an array of input to the session.
	 */
	public function withInput(array|null $input = null): static
	{
		$this->session->flashInput($this->removeFilesFromInput(
			! is_null($input) ? $input : $this->request->input()
		));

		return $this;
	}

	/**
	 * Remove any fragment identifier from the response URL.
	 */
	public function withoutFragment(): static
	{
		return $this->setTargetUrl(Str::before($this->getTargetUrl(), '#'));
	}

	/**
	 * Dynamically bind flash data in the session.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		if (str_starts_with($method, 'with')) {
			return $this->with(Str::snake(substr($method, 4)), $parameters[0]);
		}

		static::throwBadMethodCallException($method);
	}
}
