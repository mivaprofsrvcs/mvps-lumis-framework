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

namespace MVPS\Lumis\Framework\Http\Client;

use ArrayAccess;
use Closure;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Http\Client\Cookies\CookieJar;
use MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException;
use MVPS\Lumis\Framework\Http\Client\Traits\DeterminesStatusCode;
use MVPS\Lumis\Framework\Support\Stringable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Response implements ArrayAccess, Stringable
{
	use DeterminesStatusCode;
	use Macroable {
		__call as macroCall;
	}

	/**
	 * The request cookies.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Cookies\CookieJar
	 */
	public CookieJar|null $cookies = null;

	/**
	 * The decoded JSON response.
	 *
	 * @var array|null
	 */
	protected array|null $decoded = null;

	/**
	 * The underlying PSR response.
	 *
	 * @var \Psr\Http\Message\ResponseInterface
	 */
	protected ResponseInterface $response;

	/**
	 * The transfer stats for the request.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\TransferStats|null
	 */
	public TransferStats|null $transferStats = null;

	/**
	 * Create a new response instance.
	 */
	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
	}

	/**
	 * Get the body of the response.
	 */
	public function body(): string
	{
		return (string) $this->response->getBody();
	}

	/**
	 * Determine if the response indicates a client error occurred.
	 */
	public function clientError(): bool
	{
		return $this->status() >= 400 && $this->status() < 500;
	}

	/**
	 * Close the stream and any underlying resources.
	 */
	public function close(): static
	{
		$this->response->getBody()->close();

		return $this;
	}

	/**
	 * Get the JSON decoded body of the response as a collection.
	 */
	public function collect(string|null $key = null): Collection
	{
		return Collection::make($this->json($key));
	}

	/**
	 * Get the response cookies.
	 */
	public function cookies(): CookieJar
	{
		return $this->cookies;
	}

	/**
	 * Get the effective URI of the response.
	 */
	public function effectiveUri(): UriInterface|null
	{
		return $this->transferStats?->getEffectiveUri();
	}

	/**
	 * Determine if the response indicates a client or server error occurred.
	 */
	public function failed(): bool
	{
		return $this->serverError() || $this->clientError();
	}

	/**
	 * Get the handler stats of the response.
	 */
	public function handlerStats(): array
	{
		return $this->transferStats?->getHandlerStats() ?? [];
	}

	/**
	 * Get a header from the response.
	 */
	public function header(string $header): string
	{
		return $this->response->getHeaderLine($header);
	}

	/**
	 * Get the headers from the response.
	 */
	public function headers(): array
	{
		return $this->response->getHeaders();
	}

	/**
	 * Get the JSON decoded body of the response as an array or scalar value.
	 */
	public function json(string|null $key = null, mixed $default = null): mixed
	{
		if (! $this->decoded) {
			$this->decoded = json_decode($this->body(), true);
		}

		if (is_null($key)) {
			return $this->decoded;
		}

		return data_get($this->decoded, $key, $default);
	}

	/**
	 * Get the JSON decoded body of the response as an object.
	 */
	public function object(): object|null
	{
		return json_decode($this->body(), false);
	}

	/**
	 * Execute the given callback if there was a server or client error.
	 */
	public function onError(callable $callback): static
	{
		if ($this->failed()) {
			$callback($this);
		}

		return $this;
	}

	/**
	 * Determine if the given offset exists.
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->json()[$offset]);
	}

	/**
	 * Get the value for a given offset.
	 */
	public function offsetGet($offset): mixed
	{
		return $this->json()[$offset];
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @throws \LogicException
	 */
	public function offsetSet($offset, $value): void
	{
		throw new LogicException('Response data may not be mutated using array access.');
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @throws \LogicException
	 */
	public function offsetUnset($offset): void
	{
		throw new LogicException('Response data may not be mutated using array access.');
	}

	/**
	 * Get the reason phrase of the response.
	 */
	public function reason(): string
	{
		return $this->response->getReasonPhrase();
	}

	/**
	 * Determine if the response was a redirect.
	 */
	public function redirect(): bool
	{
		return $this->status() >= 300 && $this->status() < 400;
	}

	/**
	 * Determine if the response indicates a server error occurred.
	 */
	public function serverError(): bool
	{
		return $this->status() >= 500;
	}

	/**
	 * Get the status code of the response.
	 */
	public function status(): int
	{
		return (int) $this->response->getStatusCode();
	}

	/**
	 * Determine if the request was successful.
	 */
	public function successful(): bool
	{
		return $this->status() >= 200 && $this->status() < 300;
	}

	/**
	 * Throw an exception if a server or client error occurred.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throw(): static
	{
		$callback = func_get_args()[0] ?? null;

		if ($this->failed()) {
			throw tap($this->toException(), function ($exception) use ($callback) {
				if ($callback && is_callable($callback)) {
					$callback($this, $exception);
				}
			});
		}

		return $this;
	}

	/**
	 * Throw an exception if a server or client error occurred and the given
	 * condition evaluates to true.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throwIf(Closure|bool $condition): static
	{
		return value($condition, $this) ?
			$this->throw(func_get_args()[1] ?? null)
			: $this;
	}

	/**
	 * Throw an exception if the response status code is a 4xx level code.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throwIfClientError(): static
	{
		return $this->clientError() ? $this->throw() : $this;
	}

	/**
	 * Throw an exception if the response status code is a 5xx level code.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throwIfServerError(): static
	{
		return $this->serverError() ? $this->throw() : $this;
	}

	/**
	 * Throw an exception if the response status code matches the given code.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throwIfStatus(callable|int $statusCode): static
	{
		if (is_callable($statusCode) && $statusCode($this->status(), $this)) {
			return $this->throw();
		}

		return $this->status() === $statusCode ? $this->throw() : $this;
	}

	/**
	 * Throw an exception unless the response status code matches the given code.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException
	 */
	public function throwUnlessStatus(callable|int $statusCode)
	{
		if (is_callable($statusCode)) {
			return $statusCode($this->status(), $this) ? $this : $this->throw();
		}

		return $this->status() === $statusCode ? $this : $this->throw();
	}

	/**
	 * Create an exception if a server or client error occurred.
	 */
	public function toException(): RequestException|null
	{
		if ($this->failed()) {
			return new RequestException($this);
		}

		return null;
	}

	/**
	 * Get the underlying PSR response for the response.
	 */
	public function toPsrResponse(): ResponseInterface
	{
		return $this->response;
	}

	/**
	 * Dynamically proxy other methods to the underlying response.
	 */
	public function __call(string $method, array $parameters): mixed
	{
		return static::hasMacro($method)
			? $this->macroCall($method, $parameters)
			: $this->response->{$method}(...$parameters);
	}

	/**
	 * Get the body of the response.
	 */
	public function __toString(): string
	{
		return $this->body();
	}
}
