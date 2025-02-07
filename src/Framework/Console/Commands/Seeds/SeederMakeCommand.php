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

namespace MVPS\Lumis\Framework\Console\Commands\Seeds;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:seeder')]
class SeederMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new seeder class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:seeder';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Seeder';

	/**
	 * Get the destination class path.
	 */
	protected function getPath(string $name): string
	{
		$name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

		if (is_dir($this->lumis->databasePath() . '/seeds')) {
			return $this->lumis->databasePath() . '/seeds/' . $name . '.php';
		}

		return $this->lumis->databasePath() . '/seeders/' . $name . '.php';
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/seeder.stub');
	}

	/**
	 * Execute the make seeder command.
	 */
	public function handle(): bool|null
	{
		parent::handle();

		return null;
	}

	/**
	 * Resolve the fully-qualified path to the stub.
	 */
	protected function resolveStubPath(string $stub): string
	{
		$customPath = $this->lumis->basePath(trim($stub, '/'));

		return is_file($customPath)
			? $customPath
			: __DIR__ . $stub;
	}

	/**
	 * Get the root namespace for the class.
	 */
	protected function rootNamespace(): string
	{
		return 'Database\Seeders\\';
	}
}
