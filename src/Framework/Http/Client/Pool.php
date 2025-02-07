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
