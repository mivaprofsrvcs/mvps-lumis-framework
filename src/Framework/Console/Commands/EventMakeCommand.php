<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:event')]
class EventMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new event class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:event';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Event';

	/**
	 * Determine if the class already exists.
	 */
	protected function alreadyExists(string $rawName): bool
	{
		return class_exists($rawName) ||
			$this->files->exists($this->getPath($this->qualifyClass($rawName)));
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Events';
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
				'Create the class even if the event already exists'
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/event.stub');
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
