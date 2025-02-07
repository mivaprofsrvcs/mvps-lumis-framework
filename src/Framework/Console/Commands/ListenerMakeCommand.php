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

use function Laravel\Prompts\suggest;

#[AsCommand(name: 'make:listener')]
class ListenerMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new event listener class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:listener';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Listener';

	/**
	 * Determine if the class already exists.
	 */
	protected function alreadyExists(string $rawName): bool
	{
		return class_exists($rawName);
	}

	/**
	 * Interact further with the user if they were prompted for
	 * missing arguments.
	 */
	protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
	{
		if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
			return;
		}

		$event = suggest(
			'What event should be listened for? (Optional)',
			$this->possibleEvents(),
		);

		if ($event) {
			$input->setOption('event', $event);
		}
	}

	/**
	 * Build the class with the given name.
	 */
	protected function buildClass(string $name): string
	{
		$event = $this->option('event') ?? '';

		if (! Str::startsWith($event, [$this->lumis->getNamespace(), 'MVPS\Lumis', '\\'])) {
			$event = $this->lumis->getNamespace() . 'Events\\' . str_replace('/', '\\', $event);
		}

		$stub = str_replace(
			['DummyEvent', '{{ event }}'],
			class_basename($event),
			parent::buildClass($name)
		);

		return str_replace(
			['DummyFullEvent', '{{ eventNamespace }}'],
			trim($event, '\\'),
			$stub
		);
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Listeners';
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'event',
				'e',
				InputOption::VALUE_OPTIONAL,
				'The event class being listened for',
			],
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the listener already exists',
			],
			// [
			// 	'queued',
			// 	null,
			// 	InputOption::VALUE_NONE,
			// 	'Indicates the event listener should be queued',
			// ],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		// if ($this->option('queued')) {
		// 	return $this->option('event')
		// 		? $this->resolveStubPath('/stubs/listener.typed.queued.stub')
		// 		: $this->resolveStubPath('/stubs/listener.queued.stub');
		// }

		return $this->option('event')
			? $this->resolveStubPath('/stubs/listener.typed.stub')
			: $this->resolveStubPath('/stubs/listener.stub');
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
