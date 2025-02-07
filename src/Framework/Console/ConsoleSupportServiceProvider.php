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
