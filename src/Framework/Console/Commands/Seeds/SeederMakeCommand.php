<?php

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
