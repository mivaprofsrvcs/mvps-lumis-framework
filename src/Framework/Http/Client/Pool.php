<?php

namespace MVPS\Lumis\Framework\Http\Client;

use MVPS\Lumis\Framework\Contracts\Http\Client\Promise;

class Pool
{
	/**
	 * The factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Factory
	 */
	protected Factory $factory;

	/**
	 * The handler function for the Guzzle client.
	 *
	 * @var callable
	 */
	protected $handler;

	/**
	 * The pool of requests.
	 *
	 * @var array
	 */
	protected array $pool = [];

	/**
	 * Create a new requests pool.
	 *
	 * TODO: Implement handler
	 */
	public function __construct(Factory|null $factory = null)
	{
		$this->factory = $factory ?: new Factory();
		// $this->handler = Utils::chooseHandler();
	}

	/**
	 * Add a request to the pool with a key.
	 */
	public function as(string $key): PendingRequest
	{
		return $this->pool[$key] = $this->asyncRequest();
	}

	/**
	 * Retrieve a new async pending request.
	 */
	protected function asyncRequest(): PendingRequest
	{
		return $this->factory->setHandler($this->handler)->async();
	}

	/**
	 * Retrieve the requests in the pool.
	 */
	public function getRequests(): array
	{
		return $this->pool;
	}

	/**
	 * Add a request to the pool with a numeric index.
	 */
	public function __call(string $method, array $parameters): PendingRequest|Promise
	{
		return $this->pool[] = $this->asyncRequest()->$method(...$parameters);
	}
}
