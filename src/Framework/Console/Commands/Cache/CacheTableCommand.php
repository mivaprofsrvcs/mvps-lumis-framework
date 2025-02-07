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

namespace MVPS\Lumis\Framework\Console\Commands\Cache;

use MVPS\Lumis\Framework\Console\MigrationGeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:cache-table')]
class CacheTableCommand extends MigrationGeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a migration for the cache database table';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:cache-table';

	/**
	 * Get the path to the migration stub file.
	 */
	protected function migrationStubFile(): string
	{
		return __DIR__ . '/stubs/cache.stub';
	}

	/**
	 * Get the migration table name.
	 */
	protected function migrationTableName(): string
	{
		return 'cache';
	}
}
