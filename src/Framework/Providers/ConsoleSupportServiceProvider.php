<?php

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
	/**
	 * The provider class names.
	 *
	 * @var string[]
	 */
	protected array $providers = [
		LumisServiceProvider::class,
		// ComposerServiceProvider::class,
	];
}
