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

namespace MVPS\Lumis\Framework\Http\Client\Events;

use MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException;
use MVPS\Lumis\Framework\Http\Client\Request;

class ConnectionFailed
{
	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Request
	 */
	public Request $request;

	/**
	 * The exception instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Exceptions\ConnectionException
	 */
	public ConnectionException $exception;

	/**
	 * Create a new connection failed event instance.
	 */
	public function __construct(Request $request, ConnectionException $exception)
	{
		$this->request = $request;
		$this->exception = $exception;
	}
}
