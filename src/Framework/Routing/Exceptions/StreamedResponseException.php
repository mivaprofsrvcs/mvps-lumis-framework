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

namespace MVPS\Lumis\Framework\Routing\Exceptions;

use MVPS\Lumis\Framework\Http\Response;
use RuntimeException;
use Throwable;

class StreamedResponseException extends RuntimeException
{
	/**
	 * The actual exception thrown during the stream.
	 *
	 * @var Throwable
	 */
	public Throwable $originalException;

	/**
	 * Create a new streamed response exception instance.
	 */
	public function __construct(Throwable $originalException)
	{
		$this->originalException = $originalException;

		parent::__construct($originalException->getMessage());
	}

	/**
	 * Get the actual exception thrown during the stream.
	 */
	public function getInnerException(): Throwable
	{
		return $this->originalException;
	}

	/**
	 * Render the exception.
	 */
	public function render(): Response
	{
		return new Response('');
	}
}
