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

use Closure;
use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise;
use MVPS\Lumis\Framework\Http\Client\Promises\Create;
use MVPS\Lumis\Framework\Support\Str;
use pdeans\Http\Factories\StreamFactory;
use pdeans\Http\Response as Psr7Response;

class Factory
{
	use Macroable {
		__call as macroCall;
	}

	/**
	 * The event dispatcher implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher|null
	 */
	protected Dispatcher|null $dispatcher;

	/**
	 * The middleware to apply to every request.
	 *
	 * @var array
	 */
	protected array $globalMiddleware = [];

	/**
	 * The options to apply to every request.
	 *
	 * @var \Closure|array
	 */
	protected Closure|array $globalOptions = [];

	/**
	 * Indicates that an exception should be thrown if any request is not faked.
	 *
	 * @var bool
	 */
	protected bool $preventStrayRequests = false;

	/**
	 * The recorded response array.
	 *
	 * @var array
	 */
	protected array $recorded = [];

	/**
	 * Indicates if the factory is recording requests and responses.
	 *
	 * @var bool
	 */
	protected bool $recording = false;

	/**
	 * All created response sequences.
	 *
	 * @var array
	 */
	protected array $responseSequences = [];

	/**
	 * The stub callables that will handle requests.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection
	 */
	protected Collection $stubCallbacks;

	/**
	 * Create a new HTTP client factory instance.
	 */
	public function __construct(Dispatcher|null $dispatcher = null)
	{
		$this->dispatcher = $dispatcher;

		$this->stubCallbacks = new Collection();
	}

	/**
	 * Indicate that an exception should not be thrown if any request is not faked.
	 */
	public function allowStrayRequests(): static
	{
		return $this->preventStrayRequests(false);
	}

	/**
	 * Create a new pending request instance for this factory.
	 */
	public function createPendingRequest(): PendingRequest
	{
		return tap(
			$this->newPendingRequest(),
			fn ($request) => $request->stub($this->stubCallbacks)
				->preventStrayRequests($this->preventStrayRequests)
		);
	}

	/**
	 * Register a stub callable that will intercept requests and be able to
	 * return stub responses.
	 */
	public function fake(callable|array|null $callback = null): static
	{
		$this->record();

		$this->recorded = [];

		if (is_null($callback)) {
			$callback = fn () => static::response();
		}

		if (is_array($callback)) {
			foreach ($callback as $url => $callable) {
				$this->stubUrl($url, $callable);
			}

			return $this;
		}

		$this->stubCallbacks = $this->stubCallbacks->merge(collection([
			function ($request, $options) use ($callback) {
				$response = $callback instanceof Closure
					? $callback($request, $options)
					: $callback;

				if ($response instanceof Promise) {
					$options['on_stats'](new TransferStats(
						$request->toPsrRequest(),
						$response->wait(),
					));
				}

				return $response;
			},
		]));

		return $this;
	}

	/**
	 * Register a response sequence for the given URL pattern.
	 */
	public function fakeSequence(string $url = '*'): ResponseSequence
	{
		return tap($this->sequence(), function ($sequence) use ($url) {
			$this->fake([$url => $sequence]);
		});
	}

	/**
	 * Get the current event dispatcher implementation.
	 */
	public function getDispatcher(): Dispatcher|null
	{
		return $this->dispatcher;
	}

	/**
	 * Get the array of global middleware.
	 */
	public function getGlobalMiddleware(): array
	{
		return $this->globalMiddleware;
	}

	/**
	 * Add middleware to apply to every request.
	 */
	public function globalMiddleware(callable $middleware): static
	{
		$this->globalMiddleware[] = $middleware;

		return $this;
	}

	/**
	 * Set the options to apply to every request.
	 */
	public function globalOptions(Closure|array $options): static
	{
		$this->globalOptions = $options;

		return $this;
	}

	/**
	 * Add request middleware to apply to every request.
	 */
	public function globalRequestMiddleware(callable $middleware): static
	{
		$this->globalMiddleware[] = Middleware::mapRequest($middleware);

		return $this;
	}

	/**
	 * Add response middleware to apply to every request.
	 */
	public function globalResponseMiddleware(callable $middleware): static
	{
		$this->globalMiddleware[] = Middleware::mapResponse($middleware);

		return $this;
	}

	/**
	 * Instantiate a new pending request instance for this factory.
	 */
	protected function newPendingRequest(): PendingRequest
	{
		return (new PendingRequest($this, $this->globalMiddleware))
			->withOptions(value($this->globalOptions));
	}

	/**
	 * Indicate that an exception should be thrown if any request is not faked.
	 */
	public function preventStrayRequests(bool $prevent = true): static
	{
		$this->preventStrayRequests = $prevent;

		return $this;
	}

	/**
	 * Begin recording request / response pairs.
	 */
	protected function record(): static
	{
		$this->recording = true;

		return $this;
	}

	/**
	 * Get a collection of the request / response pairs matching the given truth test.
	 */
	public function recorded(callable|null $callback = null): Collection
	{
		if (empty($this->recorded)) {
			return collection();
		}

		$callback = $callback ?: fn () => true;

		return collection($this->recorded)->filter(fn ($pair) => $callback($pair[0], $pair[1]));
	}

	/**
	 * Record a request response pair.
	 */
	public function recordRequestResponsePair(Request $request, Response $response): void
	{
		if (! $this->recording) {
			return;
		}

		$this->recorded[] = [$request, $response];
	}

	/**
	 * Create a new response instance for use during stubbing.
	 */
	public static function response(array|string|null $body = null, $status = 200, $headers = [])
	{
		if (is_array($body)) {
			$body = json_encode($body);

			$headers['Content-Type'] = 'application/json';
		}

		$response = new Psr7Response(
			(new StreamFactory)->createStream($body),
			$status,
			$headers
		);

		return Create::promiseFor($response);
	}

	/**
	 * Get an invokable object that returns a sequence of responses in order for use during stubbing.
	 */
	public function sequence(array $responses = []): ResponseSequence
	{
		return $this->responseSequences[] = new ResponseSequence($responses);
	}

	/**
	 * Stub the given URL using the given callback.
	 */
	public function stubUrl(string $url, Response|Promise|callable $callback): static
	{
		return $this->fake(function ($request, $options) use ($url, $callback) {
			if (! Str::is(Str::start($url, '*'), $request->url())) {
				return;
			}

			return $callback instanceof Closure || $callback instanceof ResponseSequence
				? $callback($request, $options)
				: $callback;
		});
	}

	/**
	 * Execute a method against a new pending request instance.
	 */
	public function __call(string $method, array $parameters): mixed
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		return $this->createPendingRequest()->{$method}(...$parameters);
	}
}
