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

namespace MVPS\Lumis\Framework\Http\Exceptions;

use MVPS\Lumis\Framework\Http\Response;
use RuntimeException;

class HttpResponseException extends RuntimeException
{
	/**
	 * The underlying response instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Response
	 */
	protected Response $response;

	/**
	 * Create a new HTTP response exception instance.
	 */
	public function __construct(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * Get the underlying response instance.
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}
}
