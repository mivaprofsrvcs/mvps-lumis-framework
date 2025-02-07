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
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'make:model')]
class ModelMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new Eloquent model class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:model';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Model';

	/**
	 * Interact further with the user if they were prompted for missing arguments.
	 */
	protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
	{
		if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
			return;
		}

		collection(multiselect('Would you like any of the following?', [
			'seed' => 'Database Seeder',
			'factory' => 'Factory',
			'migration' => 'Migration',
			'resource' => 'Resource Controller',
		]))->each(fn ($option) => $input->setOption($option, true));
	}

	/**
	 * Create a controller for the model.
	 */
	protected function createController(): void
	{
		$controller = Str::studly(class_basename($this->argument('name')));

		$modelName = $this->qualifyClass($this->getNameInput());

		$this->call('make:controller', array_filter([
			'name' => "{$controller}Controller",
			'--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
			'--api' => $this->option('api'),
		]));
	}

	/**
	 * Create a model factory for the model.
	 */
	protected function createFactory(): void
	{
		$factory = Str::studly($this->argument('name'));

		$this->call('make:factory', [
			'name' => "{$factory}Factory",
			'--model' => $this->qualifyClass($this->getNameInput()),
		]);
	}

	/**
	 * Create a migration file for the model.
	 */
	protected function createMigration(): void
	{
		$table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

		if ($this->option('pivot')) {
			$table = Str::singular($table);
		}

		$this->call('make:migration', [
			'name' => "create_{$table}_table",
			'--create' => $table,
		]);
	}

	/**
	 * Create a seeder file for the model.
	 */
	protected function createSeeder(): void
	{
		$seeder = Str::studly(class_basename($this->argument('name')));

		$this->call('make:seeder', [
			'name' => "{$seeder}Seeder",
		]);
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Models';
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'all',
				'a',
				InputOption::VALUE_NONE,
				'Generate a migration, seeder, factory, and resource controller for the model',
			],
			[
				'controller',
				'c',
				InputOption::VALUE_NONE,
				'Create a new controller for the model',
			],
			[
				'factory',
				'f',
				InputOption::VALUE_NONE,
				'Create a new factory for the model',
			],
			[
				'force',
				null,
				InputOption::VALUE_NONE,
				'Create the class even if the model already exists',
			],
			[
				'migration',
				'm',
				InputOption::VALUE_NONE,
				'Create a new migration file for the model',
			],
			[
				'morph-pivot',
				null,
				InputOption::VALUE_NONE,
				'Indicates if the generated model should be a custom polymorphic intermediate table model',
			],
			[
				'seed',
				's',
				InputOption::VALUE_NONE,
				'Create a new seeder for the model',
			],
			[
				'pivot',
				'p',
				InputOption::VALUE_NONE,
				'Indicates if the generated model should be a custom intermediate table model',
			],
			[
				'resource',
				'r',
				InputOption::VALUE_NONE,
				'Indicates if the generated controller should be a resource controller',
			],
			[
				'api',
				null,
				InputOption::VALUE_NONE,
				'Indicates if the generated controller should be an API resource controller',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		if ($this->option('pivot')) {
			return $this->resolveStubPath('/stubs/model.pivot.stub');
		}

		if ($this->option('morph-pivot')) {
			return $this->resolveStubPath('/stubs/model.morph.pivot.stub');
		}

		return $this->resolveStubPath('/stubs/model.stub');
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): bool|null
	{
		if (parent::handle() === false && ! $this->option('force')) {
			return false;
		}

		if ($this->option('all')) {
			$this->input->setOption('factory', true);
			$this->input->setOption('seed', true);
			$this->input->setOption('migration', true);
			$this->input->setOption('controller', true);
			$this->input->setOption('resource', true);
		}

		if ($this->option('factory')) {
			$this->createFactory();
		}

		if ($this->option('migration')) {
			$this->createMigration();
		}

		if ($this->option('seed')) {
			$this->createSeeder();
		}

		if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
			$this->createController();
		}

		return null;
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
