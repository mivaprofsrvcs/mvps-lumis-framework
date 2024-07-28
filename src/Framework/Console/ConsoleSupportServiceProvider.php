<?php

namespace MVPS\Lumis\Framework\Console;

use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Database\MigrationServiceProvider;
use MVPS\Lumis\Framework\Providers\AggregateServiceProvider;
use MVPS\Lumis\Framework\Support\ComposerServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
	/**
	 * The provider class names.
	 *
	 * @var array<string>
	 */
	protected array $providers = [
		LumisServiceProvider::class,
		MigrationServiceProvider::class,
		ComposerServiceProvider::class,
	];
}
