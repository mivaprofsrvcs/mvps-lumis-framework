<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:factory')]
class FactoryMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new model factory';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:factory';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Factory';

	/**
	 * Build the class with the given name.
	 */
	protected function buildClass(string $name): string
	{
		$factory = class_basename(Str::ucfirst(str_replace('Factory', '', $name)));

		$namespaceModel = $this->option('model')
			? $this->qualifyModel($this->option('model'))
			: $this->qualifyModel($this->guessModelName($name));

		$model = class_basename($namespaceModel);

		$namespace = $this->getNamespace(Str::replaceFirst(
			$this->rootNamespace(),
			'Database\\Factories\\',
			$this->qualifyClass($this->getNameInput())
		));

		$replace = [
			'{{ factoryNamespace }}' => $namespace,
			'NamespacedDummyModel' => $namespaceModel,
			'{{ namespacedModel }}' => $namespaceModel,
			'{{namespacedModel}}' => $namespaceModel,
			'DummyModel' => $model,
			'{{ model }}' => $model,
			'{{model}}' => $model,
			'{{ factory }}' => $factory,
			'{{factory}}' => $factory,
		];

		return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The name of the model',
			],
		];
	}

	/**
	 * Get the destination class path.
	 */
	protected function getPath(string $name): string
	{
		$name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Factory');

		return $this->lumis->databasePath() . '/factories/' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/factory.stub');
	}

	/**
	 * Guess the model name from the Factory name or return a default model name.
	 */
	protected function guessModelName(string $name): string
	{
		$key = 'Factory';

		if (str_ends_with($name, $key)) {
			$name = substr($name, 0, -(strlen($key)));
		}

		$modelName = $this->qualifyModel(Str::after($name, $this->rootNamespace()));

		if (class_exists($modelName)) {
			return $modelName;
		}

		return $this->rootNamespace() . 'Models\Model';
	}

	/**
	 * Resolve the fully-qualified path to the stub.
	 */
	protected function resolveStubPath($stub): string
	{
		$customPath = $this->lumis->basePath(trim($stub, '/'));

		return file_exists($customPath)
			? $customPath
			: __DIR__ . $stub;
	}
}
