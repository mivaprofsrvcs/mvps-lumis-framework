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
use MVPS\Lumis\Framework\Http\Request;

class ConvertEmptyStringsToNull extends TransformsRequest
{
	/**
	 * All of the registered skip callbacks.
	 *
	 * @var array
	 */
	protected static array $skipCallbacks = [];

	/**
	 * Flush the middleware's global state.
	 */
	public static function flushState(): void
	{
		static::$skipCallbacks = [];
	}

	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		foreach (static::$skipCallbacks as $callback) {
			if ($callback($request)) {
				return $next($request);
			}
		}

		return parent::handle($request, $next);
	}

	/**
	 * Register a callback that instructs the middleware to be skipped.
	 */
	public static function skipWhen(Closure $callback): void
	{
		static::$skipCallbacks[] = $callback;
	}

	/**
	 * Transform the given value.
	 */
	protected function transform(string $key, mixed $value): mixed
	{
		return $value === '' ? null : $value;
	}
}
