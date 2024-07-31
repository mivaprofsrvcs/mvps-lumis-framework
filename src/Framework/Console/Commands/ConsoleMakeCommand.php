<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:command')]
class ConsoleMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new Lumis command';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:command';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Console command';

	/**
	 * Get the console command arguments.
	 */
	protected function getArguments(): array
	{
		return [
			[
				'name',
				InputArgument::REQUIRED,
				'The name of the command',
			],
		];
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Console\Commands';
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the console command already exists',
			],
			[
				'command',
				null,
				InputOption::VALUE_OPTIONAL,
				'The terminal command that will be used to invoke the class',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		$relativePath = '/stubs/console.stub';
		$customPath = $this->lumis->basePath(trim($relativePath, '/'));

		return file_exists($customPath)
			? $customPath
			: __DIR__ . $relativePath;
	}

	/**
	 * Replace the class name for the given stub.
	 */
	protected function replaceClass(string $stub, string $name): string
	{
		$stub = parent::replaceClass($stub, $name);

		$command = $this->option('command') ?: 'app:' . Str::of($name)->classBasename()->kebab()->value();

		return str_replace(['dummy:command', '{{ command }}'], $command, $stub);
	}
}
