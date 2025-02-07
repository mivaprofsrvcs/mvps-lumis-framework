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

use Closure;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use RuntimeException;

class LocalFilesystemAdapter extends FilesystemAdapter
{
	use Conditionable;

	/**
	 * The name of the filesystem disk.
	 *
	 * @var string
	 */
	protected string $disk = '';

	/**
	 * Indicates if signed URLs should serve corresponding files.
	 *
	 * @var bool
	 */
	protected bool $shouldServeSignedUrls = false;

	/**
	 * The Closure that should be used to resolve the URL generator.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $urlGeneratorResolver = null;

	/**
	 * Specify the name of the disk the adapter is managing.
	 *
	 * @param  string  $disk
	 * @return $this
	 */
	public function diskName(string $disk)
	{
		$this->disk = $disk;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function providesTemporaryUrls(): bool
	{
		return $this->temporaryUrlCallback || (
			$this->shouldServeSignedUrls && $this->urlGeneratorResolver instanceof Closure
		);
	}

	/**
	 * Indicate that signed URLs should serve the corresponding files.
	 */
	public function shouldServeSignedUrls(bool $serve = true, Closure|null $urlGeneratorResolver = null): static
	{
		$this->shouldServeSignedUrls = $serve;
		$this->urlGeneratorResolver = $urlGeneratorResolver;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
	{
		if ($this->temporaryUrlCallback) {
			return $this->temporaryUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
		}

		if (! $this->providesTemporaryUrls()) {
			throw new RuntimeException('This driver does not support creating temporary URLs.');
		}

		$url = call_user_func($this->urlGeneratorResolver);

		return $url->to(
			$url->temporarySignedRoute(
				'storage.' . $this->disk,
				$expiration,
				['path' => $path],
				absolute: false
			)
		);
	}
}
