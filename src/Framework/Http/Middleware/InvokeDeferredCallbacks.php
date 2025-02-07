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

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class InvokeDeferredCallbacks
{
	/**
	 * Handle the incoming request.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		return $next($request);
	}

	/**
	 * Invoke the deferred callbacks.
	 */
	public function terminate(Request $request, Response $response): void
	{
		Container::getInstance()
			->make(DeferredCallbackCollection::class)
			->invokeWhen(fn ($callback) => $response->getStatusCode() < 400 || $callback->always);
	}
}
