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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\{confirm};

#[AsCommand(name: 'make:exception')]
class ExceptionMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new custom exception class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:exception';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Exception';

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
	{
		if ($this->didReceiveOptions($input)) {
			return;
		}

		$input->setOption('report', confirm('Should the exception have a report method?', default: false));
		$input->setOption('render', confirm('Should the exception have a render method?', default: false));
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function alreadyExists(string $rawName): bool
	{
		return class_exists($this->rootNamespace() . 'Exceptions\\' . $rawName);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Exceptions';
	}

	/**
	 * Get the exception make command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the exception already exists',
			],
			[
				'render',
				null,
				InputOption::VALUE_NONE,
				'Create the exception with an empty render method',
			],
			[
				'report',
				null,
				InputOption::VALUE_NONE,
				'Create the exception with an empty report method',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		if ($this->option('render')) {
			return $this->option('report')
				? __DIR__ . '/stubs/exception-render-report.stub'
				: __DIR__ . '/stubs/exception-render.stub';
		}

		return $this->option('report')
			? __DIR__ . '/stubs/exception-report.stub'
			: __DIR__ . '/stubs/exception.stub';
	}
}
