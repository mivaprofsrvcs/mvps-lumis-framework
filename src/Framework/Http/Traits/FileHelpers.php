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

use MVPS\Lumis\Framework\Support\Str;

trait FileHelpers
{
	/**
	 * The cache copy of the file's hash name.
	 *
	 * @var string|null
	 */
	protected string|null $hashName = null;

	/**
	 * Get the dimensions of the image (if applicable).
	 */
	public function dimensions(): array|null
	{
		return @getimagesize($this->getRealPath());
	}

	/**
	 * Get the file's extension.
	 */
	public function extension(): string
	{
		return $this->guessExtension();
	}

	/**
	 * Get a filename for the file.
	 */
	public function hashName(string|null $path = null): string
	{
		if ($path) {
			$path = rtrim($path, '/') . '/';
		}

		$hash = $this->hashName ?: $this->hashName = Str::random(40);

		if ($extension = $this->guessExtension()) {
			$extension = '.' . $extension;
		}

		return $path . $hash . $extension;
	}

	/**
	 * Get the fully qualified path to the file.
	 */
	public function path(): string
	{
		return $this->getRealPath();
	}
}
