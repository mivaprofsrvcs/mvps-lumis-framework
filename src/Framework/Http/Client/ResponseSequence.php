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
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise;
use OutOfBoundsException;

class ResponseSequence
{
	use Macroable;

	/**
	 * The response that should be returned when the sequence is empty.
	 */
	protected Promise|null $emptyResponse = null;

	/**
	 * Indicates that invoking this sequence when it is empty should throw
	 * an exception.
	 *
	 * @var bool
	 */
	protected bool $failWhenEmpty = true;

	/**
	 * The responses in the sequence.
	 *
	 * @var array
	 */
	protected array $responses;

	/**
	 * Create a new response sequence.
	 */
	public function __construct(array $responses)
	{
		$this->responses = $responses;
	}

	/**
	 * Make the sequence return a default response when it is empty.
	 */
	public function dontFailWhenEmpty(): static
	{
		return $this->whenEmpty(Factory::response());
	}

	/**
	 * Indicate that this sequence has depleted all of its responses.
	 */
	public function isEmpty(): bool
	{
		return count($this->responses) === 0;
	}

	/**
	 * Push a response to the sequence.
	 */
	public function push(string|array|null $body = null, int $status = 200, array $headers = []): static
	{
		return $this->pushResponse(Factory::response($body, $status, $headers));
	}

	/**
	 * Push response with the contents of a file as the body to the sequence.
	 */
	public function pushFile(string $filePath, int $status = 200, array $headers = []): static
	{
		$string = file_get_contents($filePath);

		return $this->pushResponse(Factory::response($string, $status, $headers));
	}

	/**
	 * Push a response to the sequence.
	 */
	public function pushResponse(mixed $response): static
	{
		$this->responses[] = $response;

		return $this;
	}

	/**
	 * Push a response with the given status code to the sequence.
	 */
	public function pushStatus(int $status, array $headers = []): static
	{
		return $this->pushResponse(Factory::response('', $status, $headers));
	}

	/**
	 * Make the sequence return a default response when it is empty.
	 */
	public function whenEmpty(Promise|Closure $response): static
	{
		$this->failWhenEmpty = false;
		$this->emptyResponse = $response;

		return $this;
	}

	/**
	 * Get the next response in the sequence.
	 *
	 * @throws \OutOfBoundsException
	 */
	public function __invoke(): mixed
	{
		if ($this->failWhenEmpty && $this->isEmpty()) {
			throw new OutOfBoundsException('A request was made, but the response sequence is empty.');
		}

		if (! $this->failWhenEmpty && $this->isEmpty()) {
			return value($this->emptyResponse ?? Factory::response());
		}

		return array_shift($this->responses);
	}
}
