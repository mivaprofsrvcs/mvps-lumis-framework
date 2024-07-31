<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Providers\ServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:provider')]
class ProviderMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new service provider class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:provider';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Provider';

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Providers';
	}

	/**
	 * Get the console command arguments.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the provider already exists',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/provider.stub');
	}

	/**
	 * Execute the console command.
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	public function handle(): bool|null
	{
		$result = parent::handle();

		if ($result === false) {
			return $result;
		}

		ServiceProvider::addProviderToBootstrapFile(
			$this->qualifyClass($this->getNameInput()),
			$this->lumis->getBootstrapProvidersPath(),
		);

		return $result;
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
