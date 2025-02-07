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

namespace MVPS\Lumis\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Throwable;

class ConnectException extends TransferException
{
	/**
	 * Additional context provided by the handler.
	 *
	 * @var array
	 */
	protected array $handlerContext;

	/**
	 * The request that caused the exception.
	 *
	 * @var RequestInterface
	 */
	protected RequestInterface $request;

	/**
	 * Create a new connect exception instance.
	 */
	public function __construct(
		string $message,
		RequestInterface $request,
		Throwable|null $previous = null,
		array $handlerContext = []
	) {
		parent::__construct($message, 0, $previous);

		$this->request = $request;
		$this->handlerContext = $handlerContext;
	}

	/**
	 * Get the request that caused the exception.
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}

	/**
	 * Gets the additional context provided by the handler.
	 */
	public function getHandlerContext(): array
	{
		return $this->handlerContext;
	}
}
