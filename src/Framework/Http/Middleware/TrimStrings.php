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
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;

class TrimStrings extends TransformsRequest
{
	/**
	 * The attributes that should not be trimmed.
	 *
	 * @var array<int, string>
	 */
	protected array $except = [
		'current_password',
		'password',
		'password_confirmation',
	];

	/**
	 * The globally ignored attributes that should not be trimmed.
	 *
	 * @var array
	 */
	protected static array $neverTrim = [];

	/**
	 * All of the registered skip callbacks.
	 *
	 * @var array
	 */
	protected static array $skipCallbacks = [];

	/**
	 * Indicate that the given attributes should never be trimmed.
	 */
	public static function except(array|string $attributes): void
	{
		static::$neverTrim = array_values(array_unique(
			array_merge(static::$neverTrim, Arr::wrap($attributes))
		));
	}

	/**
	 * Flush the middleware's global state.
	 */
	public static function flushState(): void
	{
		static::$neverTrim = [];

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
	 * Determine if the given key should be skipped.
	 */
	protected function shouldSkip(string $key, array $except): bool
	{
		return in_array($key, $except, true);
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
		$except = array_merge($this->except, static::$neverTrim);

		if ($this->shouldSkip($key, $except) || ! is_string($value)) {
			return $value;
		}

		return Str::trim($value);
	}
}
