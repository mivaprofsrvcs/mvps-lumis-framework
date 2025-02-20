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

namespace MVPS\Lumis\Framework\Filesystem;

use Exception;
use MVPS\Lumis\Framework\Http\Request;

class ServeFile
{
	/**
	 * The configuration settings for serving files.
	 *
	 * @var array
	 */
	protected array $config;

	/**
	 * The name of the disk where the files are stored.
	 *
	 * @var string
	 */
	protected string $disk;

	/**
	 * Flag to determine if the environment is in production mode.
	 *
	 * @var bool
	 */
	protected bool $isProduction;

	/**
	 * Create a new invokable controller to serve files.
	 */
	public function __construct(string $disk, array $config, bool $isProduction)
	{
		$this->disk = $disk;
		$this->config = $config;
		$this->isProduction = $isProduction;
	}

	/**
	 * Handle the incoming request.
	 */
	public function __invoke(Request $request, string $path)
	{
		abort_unless(
			$this->hasValidSignature($request),
			$this->isProduction ? 404 : 403
		);
		try {
			abort_unless(app('filesystem')->disk($this->disk)->exists($path), 404);

			$headers = [
				'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
				'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
			];

			return tap(
				app('filesystem')->disk($this->disk)->serve($request, $path, headers: $headers),
				function ($response) use ($headers) {
					if (! $response->headers->has('Content-Security-Policy')) {
						$response->headers->replace($headers);
					}
				}
			);
		} catch (Exception $e) {
			abort(404);
		}
	}

	/**
	 * Determine if the request has a valid signature if applicable.
	 */
	protected function hasValidSignature(Request $request): bool
	{
		return ($this->config['visibility'] ?? 'private') === 'public' ||
			$request->hasValidRelativeSignature();
	}
}
