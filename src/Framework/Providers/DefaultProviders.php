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

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Cache\CacheServiceProvider;
use MVPS\Lumis\Framework\Console\ConsoleSupportServiceProvider;
use MVPS\Lumis\Framework\Cookie\CookieServiceProvider;
use MVPS\Lumis\Framework\Database\DatabaseServiceProvider;
use MVPS\Lumis\Framework\Filesystem\FilesystemServiceProvider;
use MVPS\Lumis\Framework\Pipeline\PipelineServiceProvider;
use MVPS\Lumis\Framework\Translation\TranslationServiceProvider;
use MVPS\Lumis\Framework\Validation\ValidationServiceProvider;
use MVPS\Lumis\Framework\View\ViewServiceProvider;

class DefaultProviders
{
	/**
	 * The current providers.
	 *
	 * @var array
	 */
	protected array $providers;

	/**
	 * Create a new default provider collection instance.
	 */
	public function __construct(array|null $providers = null)
	{
		$this->providers = $providers ?: [
			CacheServiceProvider::class,
			ConsoleSupportServiceProvider::class,
			CookieServiceProvider::class,
			DatabaseServiceProvider::class,
			// EncryptionServiceProvider::class,
			FilesystemServiceProvider::class,
			FrameworkServiceProvider::class,
			PipelineServiceProvider::class,
			TranslationServiceProvider::class,
			ValidationServiceProvider::class,
			ViewServiceProvider::class,
		];
	}

	/**
	 * Disable the given providers.
	 */
	public function except(array $providers): static
	{
		return new static(collection($this->providers)
			->reject(fn ($provider) => in_array($provider, $providers))
			->values()
			->toArray());
	}

	/**
	 * Merge the given providers into the provider collection.
	 */
	public function merge(array $providers): static
	{
		$this->providers = array_merge($this->providers, $providers);

		return new static($this->providers);
	}

	/**
	 * Replace the given providers with other providers.
	 */
	public function replace(array $replacements): static
	{
		$current = collection($this->providers);

		foreach ($replacements as $from => $to) {
			$key = $current->search($from);

			$current = is_int($key) ? $current->replace([$key => $to]) : $current;
		}

		return new static($current->values()->toArray());
	}

	/**
	 * Convert the provider collection to an array.
	 */
	public function toArray(): array
	{
		return $this->providers;
	}
}
