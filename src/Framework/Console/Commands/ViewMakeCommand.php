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

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Support\Stimulate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:view')]
class ViewMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new view';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:view';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'View';

	/**
	 * Build the class with the given name.
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	protected function buildClass(string $name): string
	{
		$contents = parent::buildClass($name);

		return str_replace(
			'{{ quote }}',
			Stimulate::stimulants()->random(),
			$contents,
		);
	}

	/**
	 * Get the desired view name from the input.
	 */
	protected function getNameInput(): string
	{
		$name = trim($this->argument('name'));

		$name = str_replace(['\\', '.'], '/', $this->argument('name'));

		return $name;
	}

	/**
	 * Get the console command arguments.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'extension',
				null,
				InputOption::VALUE_OPTIONAL,
				'The extension of the generated view',
				'blade.php',
			],
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the view even if the view already exists',
			],
		];
	}

	/**
	 * Get the destination view path.
	 */
	protected function getPath(string $name): string
	{
		return $this->viewPath(
			$this->getNameInput() . '.' . $this->option('extension')
		);
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/view.stub');
	}

	/**
	 * Resolve the fully-qualified path to the stub.
	 */
	protected function resolveStubPath(string $stub): string
	{
		$customPath = $this->lumis->basePath(trim($stub, '/'));

		return file_exists($customPath)
			? $customPath
			: __DIR__ . $stub;
	}
}
