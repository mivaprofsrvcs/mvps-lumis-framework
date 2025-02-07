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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class TransferStats
{
	/**
	 * Data related to the error encountered during request handling.
	 *
	 * @var mixed
	 */
	private mixed $handlerErrorData;

	/**
	 * Statistics collected during request handling.
	 *
	 * @var array
	 */
	private array $handlerStats;

	/**
	 * The original HTTP request.
	 *
	 * @var \Psr\Http\Message\RequestInterface
	 */
	private RequestInterface $request;

	/**
	 * The HTTP response received (if any).
	 *
	 * @var \Psr\Http\Message\ResponseInterface|null
	 */
	private ResponseInterface|null $response;

	/**
	 * The total transfer time of the request.
	 *
	 * @var float|null
	 */
	private float|null $transferTime;

	/**
	 * Creates a new request transfer state instance.
	 */
	public function __construct(
		RequestInterface $request,
		ResponseInterface|null $response = null,
		float|null $transferTime = null,
		$handlerErrorData = null,
		array $handlerStats = []
	) {
		$this->request = $request;
		$this->response = $response;
		$this->transferTime = $transferTime;
		$this->handlerErrorData = $handlerErrorData;
		$this->handlerStats = $handlerStats;
	}

	/**
	 * Get the effective URI the request was sent to.
	 */
	public function getEffectiveUri(): UriInterface
	{
		return $this->request->getUri();
	}

	/**
	 * Gets handler specific error data.
	 */
	public function getHandlerErrorData(): mixed
	{
		return $this->handlerErrorData;
	}

	/**
	 * Get a specific handler statistic from the handler by name.
	 */
	public function getHandlerStat(string $stat): mixed
	{
		return $this->handlerStats[$stat] ?? null;
	}

	/**
	 * Gets an array of all of the handler specific transfer data.
	 */
	public function getHandlerStats(): array
	{
		return $this->handlerStats;
	}

	/**
	 * Returns the original HTTP request.
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}

	/**
	 * Returns the received HTTP response, if any.
	 */
	public function getResponse(): ResponseInterface|null
	{
		return $this->response;
	}

	/**
	 * Get the estimated time the request was being transferred by the handler.
	 */
	public function getTransferTime(): float|null
	{
		return $this->transferTime;
	}

	/**
	 * Checks if a response was received.
	 */
	public function hasResponse(): bool
	{
		return ! is_null($this->response);
	}
}
