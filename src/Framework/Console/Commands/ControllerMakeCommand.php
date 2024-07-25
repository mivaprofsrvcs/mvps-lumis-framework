<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use InvalidArgumentException;
use MVPS\Lumis\Framework\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

#[AsCommand(name: 'make:controller')]
class ControllerMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new controller class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:controller';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Controller';

	/**
	 * Interact further with the user if they were prompted for missing arguments.
	 */
	protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
	{
		if ($this->didReceiveOptions($input)) {
			return;
		}

		$type = select('Which type of controller would you like?', [
			'empty' => 'Empty',
			'resource' => 'Resource',
			'singleton' => 'Singleton',
			'api' => 'API',
			'invokable' => 'Invokable',
		]);

		if ($type !== 'empty') {
			$input->setOption($type, true);
		}

		// TODO: Implement this when adding model support.
		// if (in_array($type, ['api', 'resource', 'singleton'])) {
		// 	$model = suggest(
		// 		"What model should this {$type} controller be for? (Optional)",
		// 		$this->possibleModels()
		// 	);

		// 	if ($model) {
		// 		$input->setOption('model', $model);
		// 	}
		// }
	}

	/**
	 * Build the class with the given name.
	 *
	 * Remove the base controller import if we are already in the base namespace.
	 */
	protected function buildClass(string $name): string
	{
		$rootNamespace = $this->rootNamespace();
		$controllerNamespace = $this->getNamespace($name);

		$replace = [];

		// if ($this->option('parent')) {
		// 	$replace = $this->buildParentReplacements();
		// }

		// if ($this->option('model')) {
		// 	$replace = $this->buildModelReplacements($replace);
		// }

		if ($this->option('creatable')) {
			$replace['abort(404);'] = '//';
		}

		$baseControllerExists = file_exists($this->getPath("{$rootNamespace}Http\Controllers\Controller"));

		if ($baseControllerExists) {
			$replace["use {$controllerNamespace}\Controller;\n"] = '';
		} else {
			$replace[' extends Controller'] = '';
			$replace["use {$rootNamespace}Http\Controllers\Controller;\n"] = '';
		}

		$class = str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));

		return $class;
	}

	/**
	 * Build the model replacement values.
	 */
	protected function buildModelReplacements(array $replace): array
	{
		$modelClass = $this->parseModel($this->option('model'));

		if (
			! class_exists($modelClass) &&
			confirm("A {$modelClass} model does not exist. Do you want to generate it?", default: true)
		) {
			$this->call('make:model', ['name' => $modelClass]);
		}

		return array_merge($replace, [
			'DummyFullModelClass' => $modelClass,
			'{{ namespacedModel }}' => $modelClass,
			'{{namespacedModel}}' => $modelClass,
			'DummyModelClass' => class_basename($modelClass),
			'{{ model }}' => class_basename($modelClass),
			'{{model}}' => class_basename($modelClass),
			'DummyModelVariable' => lcfirst(class_basename($modelClass)),
			'{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
			'{{modelVariable}}' => lcfirst(class_basename($modelClass)),
		]);
	}

	/**
	 * Build the replacements for a parent controller.
	 */
	protected function buildParentReplacements(): array
	{
		$parentModelClass = $this->parseModel($this->option('parent'));

		if (
			! class_exists($parentModelClass) &&
			confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", default: true)
		) {
			$this->call('make:model', ['name' => $parentModelClass]);
		}

		return [
			'ParentDummyFullModelClass' => $parentModelClass,
			'{{ namespacedParentModel }}' => $parentModelClass,
			'{{namespacedParentModel}}' => $parentModelClass,
			'ParentDummyModelClass' => class_basename($parentModelClass),
			'{{ parentModel }}' => class_basename($parentModelClass),
			'{{parentModel}}' => class_basename($parentModelClass),
			'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
			'{{ parentModelVariable }}' => lcfirst(class_basename($parentModelClass)),
			'{{parentModelVariable}}' => lcfirst(class_basename($parentModelClass)),
		];
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Http\Controllers';
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'api',
				null,
				InputOption::VALUE_NONE,
				'Exclude the create and edit methods from the controller',
			],
			[
				'type',
				null,
				InputOption::VALUE_REQUIRED,
				'Manually specify the controller stub file to use',
			],
			[
				'force',
				null,
				InputOption::VALUE_NONE,
				'Create the class even if the controller already exists',
			],
			[
				'invokable',
				'i',
				InputOption::VALUE_NONE,
				'Generate a single method, invokable controller class',
			],
			// [
			// 	'model',
			// 	'm',
			// 	InputOption::VALUE_OPTIONAL,
			// 	'Generate a resource controller for the given model',
			// ],
			// [
			// 	'parent',
			// 	'p',
			// 	InputOption::VALUE_OPTIONAL,
			// 	'Generate a nested resource controller class',
			// ],
			[
				'resource',
				'r',
				InputOption::VALUE_NONE,
				'Generate a resource controller class',
			],
			[
				'singleton',
				's',
				InputOption::VALUE_NONE,
				'Generate a singleton resource controller class',
			],
			[
				'creatable',
				null,
				InputOption::VALUE_NONE,
				'Indicate that a singleton resource should be creatable',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		$stub = null;

		if ($type = $this->option('type')) {
			$stub = "/stubs/controller.{$type}.stub";
		// } elseif ($this->option('parent')) {
		// 	$stub = $this->option('singleton')
		// 		? '/stubs/controller.nested.singleton.stub'
		// 		: '/stubs/controller.nested.stub';
		} elseif ($this->option('invokable')) {
			$stub = '/stubs/controller.invokable.stub';
		} elseif ($this->option('singleton')) {
			$stub = '/stubs/controller.singleton.stub';
		} elseif ($this->option('resource')) {
			$stub = '/stubs/controller.stub';
		}

		if ($this->option('api') && is_null($stub)) {
			$stub = '/stubs/controller.api.stub';
		} elseif ($this->option('api') && ! is_null($stub) && ! $this->option('invokable')) {
			$stub = str_replace('.stub', '.api.stub', $stub);
		}

		$stub ??= '/stubs/controller.plain.stub';

		return $this->resolveStubPath($stub);
	}

	/**
	 * Get the fully-qualified model class name.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function parseModel(string $model): string
	{
		if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
			throw new InvalidArgumentException('Model name contains invalid characters.');
		}

		return $this->qualifyModel($model);
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
