<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:rule')]
class RuleMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new validation rule';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:rule';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Rule';

	/**
	 * {@inheritdoc}
	 */
	protected function buildClass(string $name): string
	{
		return str_replace(
			'{{ ruleType }}',
			$this->option('implicit') ? 'ImplicitRule' : 'Rule',
			parent::buildClass($name)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Rules';
	}

	/**
	 * Get the rule make command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the rule already exists',
			],
			[
				'implicit',
				'i',
				InputOption::VALUE_NONE,
				'Generate an implicit rule',
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getStub(): string
	{
		$stub = $this->option('implicit')
			? '/stubs/rule.implicit.stub'
			: '/stubs/rule.stub';

		$customPath = $this->lumis->basePath(trim($stub, '/'));

		return file_exists($customPath)
			? $customPath
			: __DIR__ . $stub;
	}
}
