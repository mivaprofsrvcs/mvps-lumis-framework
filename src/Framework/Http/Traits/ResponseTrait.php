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

namespace MVPS\Lumis\Framework\Http\Traits;

use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Throwable;

trait ResponseTrait
{
	/**
	 * The exception that triggered the error response (if applicable).
	 *
	 * @var \Throwable|null
	 */
	public Throwable|null $exception = null;

	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public mixed $original = null;

	/**
	 * Get the body content of the response.
	 */
	public function content(): string
	{
		return $this->getContent();
	}

	/**
	 * Add a cookie to the response.
	 */
	public function cookie(mixed $cookie): static
	{
		return $this->withCookie(...func_get_args());
	}

	/**
	 * Get the callback of the response.
	 *
	 * @return string|null
	 */
	public function getCallback()
	{
		return $this->callback ?? null;
	}

	/**
	 * Get the original response content.
	 */
	public function getOriginalContent(): mixed
	{
		$original = $this->original;

		return $original instanceof static ? $original->{__FUNCTION__}() : $original;
	}

	/**
	 * Set a header on the Response.
	 */
	public function header(string $key, array|string $values, bool $replace = true): static
	{
		$this->headerBag->set($key, $values, $replace);

		return $this;
	}

	/**
	 * Throws an instance of HttpResponseException with the current response.
	 *
	 * This method halts execution by throwing an exception that encapsulates the
	 * current HTTP response, allowing the framework to handle it appropriately.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException
	 */
	public function throwResponse(): void
	{
		throw new HttpResponseException($this);
	}

	/**
	 * Add a cookie to the response.
	 */
	public function withCookie(mixed $cookie): static
	{
		if (is_string($cookie) && function_exists('cookie')) {
			$cookie = cookie(...func_get_args());
		}

		$this->headerBag->setCookie($cookie);

		return $this;
	}

	/**
	 * Set the exception to attach to the response.
	 */
	public function withException(Throwable $e): static
	{
		$this->exception = $e;

		return $this;
	}

	/**
	 * Add an array of headers to the response.
	 */
	public function withHeaders(HeaderBag|array $headers): static
	{
		if ($headers instanceof HeaderBag) {
			$headers = $headers->all();
		}

		foreach ($headers as $key => $value) {
			$this->headerBag->set($key, $value);
		}

		return $this;
	}

	/**
	 * Expire a cookie when sending the response.
	 */
	public function withoutCookie(mixed $cookie, string|null $path = null, string|null $domain = null): static
	{
		if (is_string($cookie) && function_exists('cookie')) {
			$cookie = cookie($cookie, null, -2628000, $path, $domain);
		}

		$this->headerBag->setCookie($cookie);

		return $this;
	}
}
