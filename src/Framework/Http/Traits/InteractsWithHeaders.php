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

namespace MVPS\Lumis\Framework\Http\Traits;

trait InteractsWithHeaders
{
	/**
	 * Inject the provided Content-Type, if none is already present.
	 */
	public function injectContentType(string $contentType, array $headers): array
	{
		$hasContentType = array_reduce(
			array_keys($headers),
			static fn($carry, $item) => $carry ?: strtolower($item) === 'content-type',
			false
		);

		if (! $hasContentType) {
			$headers['content-type'] = [$contentType];
		}

		return $headers;
	}
}
