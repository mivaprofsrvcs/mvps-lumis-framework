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
use MVPS\Lumis\Framework\Http\Exceptions\ContentTooLargeException;
use MVPS\Lumis\Framework\Http\Request;

class ValidatePostSize
{
	/**
	 * Determine the server 'post_max_size' as bytes.
	 */
	protected function getPostMaxSize(): int
	{
		$postMaxSize = ini_get('post_max_size');

		if (is_numeric($postMaxSize)) {
			return (int) $postMaxSize;
		}

		$metric = strtoupper(substr($postMaxSize, -1));

		$postMaxSize = (int) $postMaxSize;

		return match ($metric) {
			'K' => $postMaxSize * 1024,
			'M' => $postMaxSize * 1048576,
			'G' => $postMaxSize * 1073741824,
			default => $postMaxSize,
		};
	}

	/**
	 * Handle an incoming request.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\ContentTooLargeException
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		$max = $this->getPostMaxSize();

		if ($max > 0 && $request->server('CONTENT_LENGTH') > $max) {
			throw new ContentTooLargeException;
		}

		return $next($request);
	}
}
