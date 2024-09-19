<?php

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
