<?php

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Console\ConsoleSupportServiceProvider;
use MVPS\Lumis\Framework\Filesystem\FilesystemServiceProvider;

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
			// CacheServiceProvider::class,
			ConsoleSupportServiceProvider::class,
			// EncryptionServiceProvider::class,
			FilesystemServiceProvider::class,
			// FrameworkServiceProvider::class,
			// ViewServiceProvider::class,
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
