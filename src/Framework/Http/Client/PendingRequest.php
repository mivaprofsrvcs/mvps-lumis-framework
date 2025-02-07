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
use Exception;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Http\Client\Cookies\CookieJar;
use MVPS\Lumis\Framework\Http\Client\Events\ConnectionFailed;
use MVPS\Lumis\Framework\Http\Client\Events\RequestSending;
use MVPS\Lumis\Framework\Http\Client\Events\ResponseReceived;
use MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectException;
use MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException;
use MVPS\Lumis\Framework\Http\Client\Exceptions\RequestException;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Support\Stringable;
use OutOfBoundsException;
use pdeans\Http\Client;
use pdeans\Http\Exceptions\TransferException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\VarDumper\VarDumper;

class PendingRequest
{
	use Conditionable;
	use Macroable;

	/**
	 * Whether the requests should be asynchronous.
	 *
	 * @var bool
	 */
	protected bool $async = false;

	/**
	 * The base URL for the request.
	 *
	 * @var string
	 */
	protected string $baseUrl = '';

	/**
	 * The callbacks that should execute before the request is sent.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection\Collection
	 */
	protected Collection $beforeSendingCallbacks;

	/**
	 * The request body format.
	 *
	 * @var string
	 */
	protected string $bodyFormat = '';

	/**
	 * The HTTP client instance.
	 *
	 * @var \pdeans\Http\Client|null
	 */
	protected Client|null $client = null;

	/**
	 * The request cookies.
	 *
	 * @var array
	 */
	protected array $cookies = [];

	/**
	 * The factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Factory|null
	 */
	protected Factory|null $factory;

	/**
	 * The HTTP handler.
	 *
	 * @var callable
	 */
	protected $handler;

	/**
	 * The request options that are mergeable via array_merge_recursive.
	 *
	 * @var array
	 */
	protected array $mergeableOptions = [
		'cookies',
		'form_params',
		'headers',
		'json',
		'multipart',
		'query',
	];

	/**
	 * The middleware callables added by users that will handle requests.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection\Collection
	 */
	protected Collection $middleware;

	/**
	 * The request options.
	 *
	 * @var array
	 */
	protected array $options = [];

	/**
	 * The raw body for the request.
	 *
	 * @var \Psr\Http\Message\StreamInterface|string
	 */
	protected StreamInterface|string $pendingBody = '';

	/**
	 * The pending files for the request.
	 *
	 * @var array
	 */
	protected array $pendingFiles = [];

	/**
	 * Indicates that an exception should be thrown if any request is not faked.
	 *
	 * @var bool
	 */
	protected bool $preventStrayRequests = false;

	/**
	 * The pending request promise.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Http\Client\Promise|null
	 */
	protected Promise|null $promise = null;

	/**
	 * The sent request object, if a request has been made.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Request|null
	 */
	protected Request|null $request = null;

	/**
	 * The number of milliseconds to wait between retries.
	 *
	 * @var Closure|int
	 */
	protected Closure|int $retryDelay = 100;

	/**
	 * Whether to throw an exception when all retries fail.
	 *
	 * @var bool
	 */
	protected bool $retryThrow = true;

	/**
	 * The callback that will determine if the request should be retried.
	 *
	 * @var callable|null
	 */
	protected $retryWhenCallback = null;

	/**
	 * The stub callables that will handle requests.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection\Collection|null
	 */
	protected Collection|null $stubCallbacks = null;

	/**
	 * A callback to run when throwing if a server or client error occurs.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $throwCallback = null;

	/**
	 * A callback to check if an exception should be thrown when a server
	 * or client error occurs.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $throwIfCallback = null;

	/**
	 * The transfer stats for the request.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\TransferStats
	 */
	protected TransferStats $transferStats;

	/**
	 * The number of times to try the request.
	 *
	 * @var int
	 */
	protected int $tries = 1;

	/**
	 * The parameters that can be substituted into the URL.
	 *
	 * @var array
	 */
	protected array $urlParameters = [];

	/**
	 * Create a new HTTP pending request instance.
	 */
	public function __construct(Factory|null $factory = null, array $middleware = [])
	{
		$this->factory = $factory;
		$this->middleware = new Collection($middleware);

		$this->asJson();

		$this->options = [
			'connect_timeout' => 10,
			'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
			'http_errors' => false,
			'timeout' => 30,
		];

		$this->beforeSendingCallbacks = collection([
			function (Request $request, array $options, PendingRequest $pendingRequest) {
				$pendingRequest->request = $request;
				$pendingRequest->cookies = $options['cookies'];

				$pendingRequest->dispatchRequestSendingEvent();
			}
		]);
	}

	/**
	 * Indicate the type of content that should be returned by the server.
	 */
	public function accept(string $contentType): static
	{
		return $this->withHeaders(['Accept' => $contentType]);
	}

	/**
	 * Indicate that JSON should be returned by the server.
	 */
	public function acceptJson(): static
	{
		return $this->accept('application/json');
	}

	/**
	 * Indicate the request contains form parameters.
	 */
	public function asForm(): static
	{
		return $this->bodyFormat('form_params')
			->contentType('application/x-www-form-urlencoded');
	}

	/**
	 * Indicate the request contains JSON.
	 */
	public function asJson(): static
	{
		return $this->bodyFormat('json')
			->contentType('application/json');
	}

	/**
	 * Indicate the request is a multi-part form request.
	 */
	public function asMultipart(): static
	{
		return $this->bodyFormat('multipart');
	}

	/**
	 * Toggle asynchronicity in requests.
	 */
	public function async(bool $async = true): static
	{
		$this->async = $async;

		return $this;
	}

	/**
	 * Attaches a file to the request.
	 *
	 * - $contents The file contents as a string or resource type (string|resource).
	 */
	public function attach(
		string|array $name,
		$contents = '',
		string|null $filename = null,
		array $headers = []
	): static {
		if (is_array($name)) {
			foreach ($name as $file) {
				$this->attach(...$file);
			}

			return $this;
		}

		$this->asMultipart();

		$this->pendingFiles[] = array_filter([
			'name' => $name,
			'contents' => $contents,
			'headers' => $headers,
			'filename' => $filename,
		]);

		return $this;
	}

	/**
	 * Set the base URL for the pending request.
	 */
	public function baseUrl(string $url): static
	{
		$this->baseUrl = $url;

		return $this;
	}

	/**
	 * Add a new "before sending" callback to the request.
	 */
	public function beforeSending(callable $callback): static
	{
		return tap($this, fn () => $this->beforeSendingCallbacks[] = $callback);
	}

	/**
	 * Specify the body format of the request.
	 */
	public function bodyFormat(string $format): static
	{
		return tap($this, fn () => $this->bodyFormat = $format);
	}

	/**
	 * Build the before sending handler.
	 */
	public function buildBeforeSendingHandler(): Closure
	{
		return function ($handler) {
			return function ($request, $options) use ($handler) {
				return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
			};
		};
	}

	/**
	 * Build the HTTP client.
	 *
	 * TODO: Build handler stacks
	 */
	public function buildClient(): Client
	{
		// return $this->client ?? $this->createClient($this->buildHandlerStack());
		return $this->client ?? $this->createClient();
	}

	/**
	 * Build the HTTP client handler stack.
	 *
	 * TODO: Build handler stacks
	 * @return HandlerStack
	 */
	public function buildHandlerStack()
	{
		// return $this->pushHandlers(HandlerStack::create($this->handler));
	}

	/**
	 * Build the recorder handler.
	 */
	public function buildRecorderHandler(): Closure
	{
		return function ($handler) {
			return function ($request, $options) use ($handler) {
				$promise = $handler($request, $options);

				return $promise->then(function ($response) use ($request, $options) {
					$this->factory?->recordRequestResponsePair(
						(new Request($request))->withData($options['lumis_data']),
						$this->newResponse($response)
					);

					return $response;
				});
			};
		};
	}

	/**
	 * Build the stub handler.
	 */
	public function buildStubHandler(): Closure
	{
		return function ($handler) {
			return function ($request, $options) use ($handler) {
				$response = ($this->stubCallbacks ?? collection())
					 ->map
					 ->__invoke((new Request($request))->withData($options['lumis_data']), $options)
					 ->filter()
					 ->first();

				if (is_null($response)) {
					if ($this->preventStrayRequests) {
						throw new RuntimeException(
							'Attempted request to [' . (string) $request->getUri() . '] without a matching fake.'
						);
					}

					return $handler($request, $options);
				}

				$response = is_array($response) ? Factory::response($response) : $response;

				$sink = $options['sink'] ?? null;

				if ($sink) {
					$response->then($this->sinkStubHandler($sink));
				}

				return $response;
			};
		};
	}

	/**
	 * Specify the connect timeout (in seconds) for the request.
	 */
	public function connectTimeout(int $seconds): static
	{
		return tap($this, fn () => $this->options['connect_timeout'] = $seconds);
	}

	/**
	 * Specify the request's content type.
	 */
	public function contentType(string $contentType): static
	{
		$this->options['headers']['Content-Type'] = $contentType;

		return $this;
	}

	/**
	 * Create a new HTTP client.
	 *
	 * TODO: Implement handler stacks
	 * @param  \HandlerStack  $handlerStack
	 * @return \Client
	 */
	// public function createClient($handlerStack): Client
	public function createClient(array $options = []): Client
	{
		return new Client($options);
	}

	/**
	 * Issue a DELETE request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function delete(string $url, array $data = []): Response
	{
		return $this->send('DELETE', $url, empty($data) ? [] : [
			$this->bodyFormat => $data,
		]);
	}

	/**
	 * Dispatch the ConnectionFailed event if a dispatcher is available.
	 */
	protected function dispatchConnectionFailedEvent(Request $request, ConnectionException $exception): void
	{
		if ($dispatcher = $this->factory?->getDispatcher()) {
			$dispatcher->dispatch(new ConnectionFailed($request, $exception));
		}
	}

	/**
	 * Dispatch the RequestSending event if a dispatcher is available.
	 */
	protected function dispatchRequestSendingEvent(): void
	{
		if ($dispatcher = $this->factory?->getDispatcher()) {
			$dispatcher->dispatch(new RequestSending($this->request));
		}
	}

	/**
	 * Dispatch the ResponseReceived event if a dispatcher is available.
	 */
	protected function dispatchResponseReceivedEvent(Response $response): void
	{
		if (! ($dispatcher = $this->factory?->getDispatcher()) || ! $this->request) {
			return;
		}

		$dispatcher->dispatch(new ResponseReceived($this->request, $response));
	}

	/**
	 * Dump the request before sending and end the script.
	 */
	public function dd(): static
	{
		$values = func_get_args();

		return $this->beforeSending(function (Request $request, array $options) use ($values) {
			foreach (array_merge($values, [$request, $options]) as $value) {
				VarDumper::dump($value);
			}

			exit(1);
		});
	}

	/**
	 * Dump the request before sending.
	 */
	public function dump(): static
	{
		$values = func_get_args();

		return $this->beforeSending(function (Request $request, array $options) use ($values) {
			foreach (array_merge($values, [$request, $options]) as $value) {
				VarDumper::dump($value);
			}
		});
	}

	/**
	 * Substitute the URL parameters in the given URL.
	 *
	 * TODO: Build UriTemplate
	 */
	protected function expandUrlParameters(string $url): string
	{
		return $url;
		// return UriTemplate::expand($url, $this->urlParameters);
	}

	/**
	 * Issue a GET request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function get(string $url, array|string|null $query = null): Response
	{
		return $this->send('GET', $url, func_num_args() === 1 ? [] : [
			'query' => $query,
		]);
	}

	/**
	 * Get the pending request options.
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * Retrieve the pending request promise.
	 */
	public function getPromise(): Promise|null
	{
		return $this->promise;
	}

	/**
	 * Retrieve a reusable HTTP client.
	 *
	 * TODO: Update this when implementing build handler stack
	 */
	protected function getReusableClient(): Client
	{
		// return $this->client ??= $this->createClient($this->buildHandlerStack());
		return $this->client ??= $this->createClient();
	}

	/**
	 * Handle the response of an asynchronous request.
	 */
	protected function handlePromiseResponse(
		Response|ConnectionException|TransferException $response,
		string $method,
		string $url,
		array $options,
		int $attempt
	): mixed {
		if ($response instanceof Response && $response->successful()) {
			return $response;
		}

		if ($response instanceof RequestException) {
			$response = $this->populateResponse($this->newResponse($response->getResponse()));
		}

		try {
			$shouldRetry = $this->retryWhenCallback ? call_user_func(
				$this->retryWhenCallback,
				$response instanceof Response ? $response->toException() : $response,
				$this
			) : true;
		} catch (Exception $exception) {
			return $exception;
		}

		if ($attempt < $this->tries && $shouldRetry) {
			$options['delay'] = value(
				$this->retryDelay,
				$attempt,
				$response instanceof Response ? $response->toException() : $response
			);

			return $this->makePromise($method, $url, $options, $attempt + 1);
		}

		if (
			$response instanceof Response &&
			$this->throwCallback &&
			(is_null($this->throwIfCallback) || call_user_func($this->throwIfCallback, $response))
		) {
			try {
				$response->throw($this->throwCallback);
			} catch (Exception $exception) {
				return $exception;
			}
		}

		if ($this->tries > 1 && $this->retryThrow) {
			return $response instanceof Response ? $response->toException() : $response;
		}

		return $response;
	}

	/**
	 * Issue a HEAD request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function head(string $url, array|string|null $query = null): Response
	{
		return $this->send('HEAD', $url, func_num_args() === 1 ? [] : [
			'query' => $query,
		]);
	}

	/**
	 * Send an asynchronous request to the given URL.
	 *
	 * TODO: Update this when implementing async
	 * @return Promise
	 */
	protected function makePromise(string $method, string $url, array $options = [], int $attempt = 1)
	{
		// return $this->promise = $this->sendRequest($method, $url, $options);
		// 	->then(function (MessageInterface $message) {
		// 		return tap($this->newResponse($message), function ($response) {
		// 			$this->populateResponse($response);
		// 			$this->dispatchResponseReceivedEvent($response);
		// 		});
		// 	})
		// 	->otherwise(function (OutOfBoundsException|TransferException $e) {
		// 		if ($e instanceof ConnectException) {
		// 			$exception = new ConnectionException($e->getMessage(), 0, $e);

		// 			$this->dispatchConnectionFailedEvent(new Request($e->getRequest()), $exception);

		// 			return $exception;
		// 		}

		// 		return $e instanceof RequestException && $e->hasResponse()
		// 			? $this->populateResponse($this->newResponse($e->getResponse()))
		// 			: $e;
		// 	})
		// 	->then(
		// 		fn (Response|ConnectionException|TransferException $response) =>
		// 			$this->handlePromiseResponse($response, $method, $url, $options, $attempt)
		// 	);
	}

	/**
	 * Specify the maximum number of redirects to allow.
	 */
	public function maxRedirects(int $max): static
	{
		return tap($this, fn () => $this->options['allow_redirects']['max'] = $max);
	}

	/**
	 * Replace the given options with the current request options.
	 */
	public function mergeOptions(array ...$options): array
	{
		return array_replace_recursive(
			array_merge_recursive($this->options, Arr::only($options, $this->mergeableOptions)),
			...$options
		);
	}

	/**
	 * Create a new response instance using the given PSR response.
	 */
	protected function newResponse(MessageInterface $response): Response
	{
		return new Response($response);
	}

	/**
	 * Normalize the given request options.
	 */
	protected function normalizeRequestOptions(array $options): array
	{
		foreach ($options as $key => $value) {
			$options[$key] = match (true) {
				is_array($value) => $this->normalizeRequestOptions($value),
				$value instanceof Stringable => $value->toString(),
				default => $value,
			};
		}

		return $options;
	}

	/**
	 * Parse the given HTTP options and set the appropriate additional options.
	 */
	protected function parseHttpOptions(array $options): array
	{
		if (isset($options[$this->bodyFormat])) {
			if ($this->bodyFormat === 'multipart') {
				$options[$this->bodyFormat] = $this->parseMultipartBodyFormat($options[$this->bodyFormat]);
			} elseif ($this->bodyFormat === 'body') {
				$options[$this->bodyFormat] = $this->pendingBody;
			}

			if (is_array($options[$this->bodyFormat])) {
				$options[$this->bodyFormat] = array_merge(
					$options[$this->bodyFormat],
					$this->pendingFiles
				);
			}
		} else {
			$options[$this->bodyFormat] = $this->pendingBody;
		}

		return collection($options)
			->map(function ($value, $key) {
				if ($key === 'json' && $value instanceof JsonSerializable) {
					return $value;
				}

				return $value instanceof Arrayable ? $value->toArray() : $value;
			})
			->all();
	}

	/**
	 * Parse multi-part form data.
	 */
	protected function parseMultipartBodyFormat(array $data): array
	{
		return collection($data)
			->map(
				fn ($value, $key) => is_array($value)
					? $value
					: ['name' => $key, 'contents' => $value]
			)
			->values()
			->all();
	}

	/**
	 * Get the request data as an array so that we can attach it to the request
	 * for convenient assertions.
	 */
	protected function parseRequestData(string $method, string $url, array $options): array
	{
		if ($this->bodyFormat === 'body') {
			return [];
		}

		$lumisData = $options[$this->bodyFormat] ?? $options['query'] ?? [];

		$urlString = Str::of($url);

		if (empty($lumisData) && $method === 'GET' && $urlString->contains('?')) {
			$lumisData = (string) $urlString->after('?');
		}

		if (is_string($lumisData)) {
			parse_str($lumisData, $parsedData);

			$lumisData = is_array($parsedData) ? $parsedData : [];
		}

		if ($lumisData instanceof JsonSerializable) {
			$lumisData = $lumisData->jsonSerialize();
		}

		return is_array($lumisData) ? $lumisData : [];
	}

	/**
	 * Issue a PATCH request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function patch(string $url, array $data = []): Response
	{
		return $this->send('PATCH', $url, [$this->bodyFormat => $data]);
	}

	/**
	 * Send a pool of asynchronous requests concurrently.
	 */
	public function pool(callable $callback): array
	{
		$results = [];

		$requests = tap(new Pool($this->factory), $callback)->getRequests();

		foreach ($requests as $key => $item) {
			$results[$key] = $item instanceof static ? $item->getPromise()->wait() : $item->wait();
		}

		return $results;
	}

	/**
	 * Populate the given response with additional data.
	 */
	protected function populateResponse(Response $response): Response
	{
		$response->cookies = $this->cookies;

		$response->transferStats = $this->transferStats;

		return $response;
	}

	/**
	 * Issue a POST request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function post(string $url, array $data = []): Response
	{
		return $this->send('POST', $url, [$this->bodyFormat => $data]);
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
	 * Add the necessary handlers to the given handler stack.
	 * TODO: Implement this with handler stack
	 * @param  HandlerStack  $handlerStack
	 * @return HandlerStack
	 */
	public function pushHandlers($handlerStack)
	{
		return tap($handlerStack, function ($stack) {
			$stack->push($this->buildBeforeSendingHandler());

			$this->middleware->each(function ($middleware) use ($stack) {
				$stack->push($middleware);
			});

			$stack->push($this->buildRecorderHandler());
			$stack->push($this->buildStubHandler());
		});
	}

	/**
	 * Issue a PUT request to the given URL.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function put(string $url, array $data = []): Response
	{
		return $this->send('PUT', $url, [
			$this->bodyFormat => $data,
		]);
	}

	/**
	 * Replace the given headers on the request.
	 */
	public function replaceHeaders(array $headers): static
	{
		$this->options['headers'] = array_merge($this->options['headers'] ?? [], $headers);

		return $this;
	}

	/**
	 * Determine if a reusable client is required.
	 */
	protected function requestsReusableClient(): bool
	{
		return ! is_null($this->client) || $this->async;
	}

	/**
	 * Specify the number of times the request should be attempted.
	 */
	public function retry(
		array|int $times,
		Closure|int $sleepMilliseconds = 0,
		callable|null $when = null,
		bool $throw = true
	): static {
		$this->tries = $times;
		$this->retryDelay = $sleepMilliseconds;
		$this->retryThrow = $throw;
		$this->retryWhenCallback = $when;

		return $this;
	}

	/**
	 * Execute the "before sending" callbacks.
	 */
	public function runBeforeSendingCallbacks(RequestInterface $request, array $options): RequestInterface
	{
		return tap($request, function (&$request) use ($options) {
			$this->beforeSendingCallbacks->each(function ($callback) use (&$request, $options) {
				$callbackResult = call_user_func(
					$callback,
					(new Request($request))->withData($options['lumis_data']),
					$options,
					$this
				);

				if ($callbackResult instanceof RequestInterface) {
					$request = $callbackResult;
				} elseif ($callbackResult instanceof Request) {
					$request = $callbackResult->toPsrRequest();
				}
			});
		});
	}

	/**
	 * Send the request to the given URL.
	 *
	 * @throws \Exception
	 * @throws \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public function send(string $method, string $url, array $options = []): Response
	{
		if (! Str::startsWith($url, ['http://', 'https://'])) {
			$url = ltrim(rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/'), '/');
		}

		$url = $this->expandUrlParameters($url);

		$options = $this->parseHttpOptions($options);

		[$this->pendingBody, $this->pendingFiles] = [null, []];

		if ($this->async) {
			return $this->makePromise($method, $url, $options);
		}

		$shouldRetry = null;

		return retry($this->tries ?? 1, function ($attempt) use ($method, $url, $options, &$shouldRetry) {
			try {
				return tap(
					$this->newResponse($this->sendRequest($method, $url, $options)),
					function ($response) use ($attempt, &$shouldRetry) {
						$this->populateResponse($response);

						$this->dispatchResponseReceivedEvent($response);

						if (! $response->successful()) {
							try {
								$shouldRetry = $this->retryWhenCallback ? call_user_func(
									$this->retryWhenCallback,
									$response->toException(),
									$this
								) : true;
							} catch (Exception $exception) {
								$shouldRetry = false;

								throw $exception;
							}

							if (
								$this->throwCallback &&
								(is_null($this->throwIfCallback) || call_user_func($this->throwIfCallback, $response))
							) {
								$response->throw($this->throwCallback);
							}

							$potentialTries = is_array($this->tries)
								? count($this->tries) + 1
								: $this->tries;

							if ($attempt < $potentialTries && $shouldRetry) {
								$response->throw();
							}

							if ($potentialTries > 1 && $this->retryThrow) {
								$response->throw();
							}
						}
					}
				);
			} catch (ConnectException $e) {
				$exception = new ConnectionException($e->getMessage(), 0, $e);

				$this->dispatchConnectionFailedEvent(new Request($e->getRequest()), $exception);

				throw $exception;
			}
		}, $this->retryDelay ?? 100, function ($exception) use (&$shouldRetry) {
			$result = $shouldRetry ?? (
				$this->retryWhenCallback ? call_user_func($this->retryWhenCallback, $exception, $this) : true
			);

			$shouldRetry = null;

			return $result;
		});
	}

	/**
	 * Send a request either synchronously or asynchronously.
	 *
	 * @throws \Exception
	 */
	protected function sendRequest(string $method, string $url, array $options = []): MessageInterface|Promise
	{
		$clientMethod = $this->async ? 'requestAsync' : 'request';

		$lumisData = $this->parseRequestData($method, $url, $options);

		$onStats = function ($transferStats) {
			$callback = $this->options['on_stats'] ?? false;

			if ($callback instanceof Closure) {
				$transferStats = $callback($transferStats) ?: $transferStats;
			}

			$this->transferStats = $transferStats;
		};

		$mergedOptions = $this->normalizeRequestOptions($this->mergeOptions([
			'lumis_data' => $lumisData,
			'on_stats' => $onStats,
		], $options));

		return $this->buildClient()->$clientMethod($method, $url, $mergedOptions);
	}

	/**
	 * Set the client instance.
	 */
	public function setClient(Client $client): static
	{
		$this->client = $client;

		return $this;
	}

	/**
	 * Create a new client instance using the given handler.
	 */
	public function setHandler(callable $handler): static
	{
		$this->handler = $handler;

		return $this;
	}

	/**
	 * Specify the path where the body of the response should be stored.
	 *
	 * - $to accepts a string or resource value (string|resource)
	 */
	public function sink($to): static
	{
		return tap($this, fn () => $this->options['sink'] = $to);
	}

	/**
	 * Get the sink stub handler callback.
	 */
	protected function sinkStubHandler(string $sink): Closure
	{
		return function ($response) use ($sink) {
			$body = $response->getBody()->getContents();

			if (is_string($sink)) {
				file_put_contents($sink, $body);

				return;
			}

			fwrite($sink, $body);
			rewind($sink);
		};
	}

	/**
	 * Register a stub callable that will intercept requests and be able to
	 * return stub responses.
	 */
	public function stub(callable $callback): static
	{
		$this->stubCallbacks = collection($callback);

		return $this;
	}

	/**
	 * Specify the timeout (in seconds) for the request.
	 */
	public function timeout(int $seconds): static
	{
		return tap($this, fn () => $this->options['timeout'] = $seconds);
	}

	/**
	 * Throw an exception if a server or client error occurs.
	 */
	public function throw(callable|null $callback = null): static
	{
		$this->throwCallback = $callback ?: fn () => null;

		return $this;
	}

	/**
	 * Throw an exception if a server or client error occurred and the given
	 * condition evaluates to true.
	 */
	public function throwIf(callable|bool $condition): static
	{
		if (is_callable($condition)) {
			$this->throwIfCallback = $condition;
		}

		return $condition ? $this->throw(func_get_args()[1] ?? null) : $this;
	}

	/**
	 * Throw an exception if a server or client error occurred and the given
	 * condition evaluates to false.
	 */
	public function throwUnless(bool $condition): static
	{
		return $this->throwIf(! $condition);
	}

	/**
	 * Specify the basic authentication username and password for the request.
	 */
	public function withBasicAuth(string $username, string $password): static
	{
		return tap($this, fn () => $this->options['auth'] = [$username, $password]);
	}

	/**
	 * Attach a raw body to the request.
	 */
	public function withBody(StreamInterface|string $content, string $contentType = 'application/json'): static
	{
		$this->bodyFormat('body');

		$this->pendingBody = $content;

		$this->contentType($contentType);

		return $this;
	}

	/**
	 * Specify the cookies that should be included with the request.
	 */
	public function withCookies(array $cookies, string $domain): static
	{
		return tap($this, function () use ($cookies, $domain) {
			$this->options = array_merge_recursive($this->options, [
				'cookies' => CookieJar::fromArray($cookies, $domain),
			]);
		});
	}

	/**
	 * Specify the digest authentication username and password for the request.
	 */
	public function withDigestAuth(string $username, string $password): static
	{
		return tap($this, fn () => $this->options['auth'] = [$username, $password, 'digest']);
	}

	/**
	 * Add the given header to the request.
	 */
	public function withHeader(string $name, mixed $value): static
	{
		return $this->withHeaders([$name => $value]);
	}

	/**
	 * Add the given headers to the request.
	 */
	public function withHeaders(array $headers): static
	{
		return tap($this, fn () => $this->options = array_merge_recursive($this->options, [
			'headers' => $headers,
		]));
	}

	/**
	 * Add new middleware the client handler stack.
	 */
	public function withMiddleware(callable $middleware): static
	{
		$this->middleware->push($middleware);

		return $this;
	}

	/**
	 * Replace the specified options on the request.
	 */
	public function withOptions(array $options): static
	{
		return tap($this, fn () => $this->options = array_replace_recursive(
			array_merge_recursive($this->options, Arr::only($options, $this->mergeableOptions)),
			$options
		));
	}

	/**
	 * Indicate that redirects should not be followed.
	 */
	public function withoutRedirecting(): static
	{
		return tap($this, fn () => $this->options['allow_redirects'] = false);
	}

	/**
	 * Indicate that TLS certificates should not be verified.
	 */
	public function withoutVerifying(): static
	{
		return tap($this, fn () => $this->options['verify'] = false);
	}

	/**
	 * Set the given query parameters in the request URI.
	 */
	public function withQueryParameters(array $parameters): static
	{
		return tap($this, fn () => $this->options = array_merge_recursive($this->options, [
			'query' => $parameters,
		]));
	}

	/**
	 * Add new request middleware the client handler stack.
	 */
	public function withRequestMiddleware(callable $middleware): static
	{
		$this->middleware->push(Middleware::mapRequest($middleware));

		return $this;
	}

	/**
	 * Add new response middleware the client handler stack.
	 */
	public function withResponseMiddleware(callable $middleware): static
	{
		$this->middleware->push(Middleware::mapResponse($middleware));

		return $this;
	}

	/**
	 * Specify an authorization token for the request.
	 */
	public function withToken(string $token, string $type = 'Bearer'): static
	{
		return tap(
			$this,
			fn () => $this->options['headers']['Authorization'] = trim($type . ' ' . $token)
		);
	}

	/**
	 * Specify the URL parameters that can be substituted into the request URL.
	 */
	public function withUrlParameters(array $parameters = []): static
	{
		return tap($this, fn () => $this->urlParameters = $parameters);
	}

	/**
	 * Specify the user agent for the request.
	 */
	public function withUserAgent(string|bool $userAgent): static
	{
		return tap(
			$this,
			fn () => $this->options['headers']['User-Agent'] = trim($userAgent)
		);
	}
}
