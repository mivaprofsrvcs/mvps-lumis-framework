<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Support\Stimulate;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new view component class';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'make:component';

	/**
	 * {@inheritdoc}
	 */
	protected string $type = 'Component';

	/**
	 * Build the class with the given name.
	 */
	protected function buildClass(string $name): string
	{
		if ($this->option('inline')) {
			return str_replace(
				['DummyView', '{{ view }}'],
				implode("\n", [
					"<<<'blade'",
					'<div>',
					"\t<!-- " . Stimulate::stimulants()->random() . ' -->',
					'</div>',
					'blade',
				]),
				parent::buildClass($name)
			);
		}

		return str_replace(
			['DummyView', '{{ view }}'],
			'view(\'components.' . $this->getView() . '\')',
			parent::buildClass($name)
		);
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\View\Components';
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
				'Create the class even if the component already exists',
			],
			[
				'inline',
				null,
				InputOption::VALUE_NONE,
				'Create a component that renders an inline view',
			],
			[
				'view',
				null,
				InputOption::VALUE_NONE,
				'Create an anonymous component with only a view',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		return $this->resolveStubPath('/stubs/view.component.stub');
	}

	/**
	 * Get the view name relative to the components directory.
	 */
	protected function getView(): string
	{
		$name = str_replace('\\', '/', $this->argument('name'));

		return collection(explode('/', $name))
			->map(function ($part) {
				return Str::kebab($part);
			})
			->implode('.');
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): bool|null
	{
		if ($this->option('view')) {
			return $this->writeView();
		}

		if (parent::handle() === false && ! $this->option('force')) {
			return false;
		}

		if (! $this->option('inline')) {
			$this->writeView();
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

	/**
	 * Write the view for the component.
	 */
	protected function writeView(): void
	{
		$path = $this->viewPath(
			str_replace('.', '/', 'components.' . $this->getView()) . '.blade.php'
		);

		if (! $this->files->isDirectory(dirname($path))) {
			$this->files->makeDirectory(dirname($path), 0777, true, true);
		}

		if ($this->files->exists($path) && ! $this->option('force')) {
			$this->components->error('View already exists.');

			return;
		}

		file_put_contents(
			$path,
			implode("\n", [
				"<div>",
				"\t<!-- " . Stimulate::stimulants()->random() . " -->",
				'</div>',
			])
		);

		$this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
	}
}
