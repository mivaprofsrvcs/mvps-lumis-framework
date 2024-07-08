<?php

namespace MVPS\Lumis\Framework\Console;

use MVPS\Lumis\Framework\Contracts\Console\PromptsForMissingInput;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

abstract class GeneratorCommand extends Command implements PromptsForMissingInput
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected Filesystem $files;

	/**
	 * Reserved names that cannot be used for generation.
	 *
	 * @var string[]
	 */
	protected array $reservedNames = [
		'__halt_compiler',
		'abstract',
		'and',
		'array',
		'as',
		'break',
		'callable',
		'case',
		'catch',
		'class',
		'clone',
		'const',
		'continue',
		'declare',
		'default',
		'die',
		'do',
		'echo',
		'else',
		'elseif',
		'empty',
		'enddeclare',
		'endfor',
		'endforeach',
		'endif',
		'endswitch',
		'endwhile',
		'enum',
		'eval',
		'exit',
		'extends',
		'false',
		'final',
		'finally',
		'fn',
		'for',
		'foreach',
		'function',
		'global',
		'goto',
		'if',
		'implements',
		'include',
		'include_once',
		'instanceof',
		'insteadof',
		'interface',
		'isset',
		'list',
		'match',
		'namespace',
		'new',
		'or',
		'parent',
		'print',
		'private',
		'protected',
		'public',
		'readonly',
		'require',
		'require_once',
		'return',
		'self',
		'static',
		'switch',
		'throw',
		'trait',
		'true',
		'try',
		'unset',
		'use',
		'var',
		'while',
		'xor',
		'yield',
		'__CLASS__',
		'__DIR__',
		'__FILE__',
		'__FUNCTION__',
		'__LINE__',
		'__METHOD__',
		'__NAMESPACE__',
		'__TRAIT__',
	];

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * Create a new generator command instance.
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Determine if the class already exists.
	 */
	protected function alreadyExists(string $rawName): bool
	{
		return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
	}

	/**
	 * Build the class with the given name.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\Filesystem\FileNotFoundException
	 */
	protected function buildClass(string $name): string
	{
		$stub = $this->files->get($this->getStub());

		return $this->replaceNamespace($stub, $name)
			->replaceClass($stub, $name);
	}

	/**
	 * Get the console command arguments.
	 */
	protected function getArguments(): array
	{
		return [
			[
				'name',
				InputArgument::REQUIRED,
				'The name of the ' . strtolower($this->type)
			],
		];
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace;
	}

	/**
	 * Get the desired class name from the input.
	 */
	protected function getNameInput(): string
	{
		return trim($this->argument('name'));
	}

	/**
	 * Get the full namespace for a given class, without the class name.
	 */
	protected function getNamespace(string $name): string
	{
		return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
	}

	/**
	 * Get the destination class path.
	 */
	protected function getPath(string $name): string
	{
		$name = Str::replaceFirst($this->rootNamespace(), '', $name);

		return $this->lumis['path'] . '/' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the stub file for the generator.
	 */
	abstract protected function getStub(): string;

	/**
	 * Execute the console command.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\Filesystem\FileNotFoundException
	 */
	public function handle(): bool|null
	{
		// First, we need to verify that the given name is not a reserved PHP keyword
		// and that the class name is valid. If the name is invalid, we should raise
		// an error now to prevent creating invalid files and polluting the filesystem.
		if ($this->isReservedName($this->getNameInput())) {
			$this->components->error(sprintf('The name "%s" is reserved by PHP.', $this->getNameInput()));

			return false;
		}

		$name = $this->qualifyClass($this->getNameInput());

		$path = $this->getPath($name);

		// Next, we will check if the class already exists. If it does, we will
		// avoid creating the class to prevent overwriting the user's code, and we
		// will exit to ensure the existing code remains untouched. If the class
		// does not exist, we will proceed with generating the necessary files.
		if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists($this->getNameInput())) {
			$this->components->error($this->type . ' already exists.');

			return false;
		}

		// Next, generate the path to the location where this class file should
		// be written. Build the class and make the necessary replacements in the
		// stub files to ensure the correct namespace and class name are applied.
		$this->makeDirectory($path);

		$this->files->put($path, $this->sortImports($this->buildClass($name)));

		$info = $this->type;

		if (windows_os()) {
			$path = str_replace('/', '\\', $path);
		}

		$this->components->info(sprintf('%s [%s] created successfully.', $info, $path));

		return null;
	}

	/**
	 * Checks whether the given name is reserved.
	 */
	protected function isReservedName(string $name): bool
	{
		return in_array(
			strtolower($name),
			collection($this->reservedNames)
				->transform(fn ($name) => strtolower($name))
				->all()
		);
	}

	/**
	 * Build the directory for the class if necessary.
	 */
	protected function makeDirectory(string $path): string
	{
		if (! $this->files->isDirectory(dirname($path))) {
			$this->files->makeDirectory(dirname($path), 0777, true, true);
		}

		return $path;
	}

	/**
	 * Get a list of possible event names.
	 */
	protected function possibleEvents(): array
	{
		$eventPath = app_path('Events');

		if (! is_dir($eventPath)) {
			return [];
		}

		return collection(Finder::create()->files()->depth(0)->in($eventPath))
			->map(fn ($file) => $file->getBasename('.php'))
			->sort()
			->values()
			->all();
	}

	/**
	 * Get a list of possible model names.
	 */
	protected function possibleModels(): array
	{
		$modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();

		return collection(Finder::create()->files()->depth(0)->in($modelPath))
			->map(fn ($file) => $file->getBasename('.php'))
			->sort()
			->values()
			->all();
	}

	/**
	 * Prompt for missing input arguments using the returned questions.
	 */
	protected function promptForMissingArgumentsUsing(): array
	{
		return [
			'name' => [
				'What should the ' . strtolower($this->type) . ' be named?',
				match ($this->type) {
					'Cast' => 'E.g. Json',
					'Console command' => 'E.g. SendEmails',
					'Component' => 'E.g. Alert',
					'Controller' => 'E.g. UserController',
					'Event' => 'E.g. PodcastProcessed',
					'Exception' => 'E.g. InvalidOrderException',
					'Middleware' => 'E.g. EnsureTokenIsValid',
					'Provider' => 'E.g. ElasticServiceProvider',
					'Resource' => 'E.g. UserResource',
					default => '',
				},
			],
		];
	}

	/**
	 * Parse the class name and format according to the root namespace.
	 */
	protected function qualifyClass(string $name): string
	{
		$name = ltrim($name, '\\/');

		$name = str_replace('/', '\\', $name);

		$rootNamespace = $this->rootNamespace();

		if (Str::startsWith($name, $rootNamespace)) {
			return $name;
		}

		return $this->qualifyClass($this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name);
	}

	/**
	 * Qualify the given model class base name.
	 */
	protected function qualifyModel(string $model): string
	{
		$model = ltrim($model, '\\/');

		$model = str_replace('/', '\\', $model);

		$rootNamespace = $this->rootNamespace();

		if (Str::startsWith($model, $rootNamespace)) {
			return $model;
		}

		return is_dir(app_path('Models'))
			? $rootNamespace . 'Models\\' . $model
			: $rootNamespace . $model;
	}

	/**
	 * Replace the class name for the given stub.
	 */
	protected function replaceClass(string $stub, string $name): string
	{
		$class = str_replace($this->getNamespace($name) . '\\', '', $name);

		return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
	}

	/**
	 * Replace the namespace for the given stub.
	 */
	protected function replaceNamespace(string &$stub, string $name): static
	{
		$searches = [
			['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
			['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
			['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
		];

		foreach ($searches as $search) {
			$stub = str_replace(
				$search,
				[$this->getNamespace($name), $this->rootNamespace()],
				$stub
			);
		}

		return $this;
	}

	/**
	 * Get the root namespace for the class.
	 */
	protected function rootNamespace(): string
	{
		return $this->lumis->getNamespace();
	}

	/**
	 * Alphabetically sorts the imports for the given stub.
	 */
	protected function sortImports(string $stub): string
	{
		if (preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
			$imports = explode("\n", trim($match['imports']));

			sort($imports);

			return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
		}

		return $stub;
	}

	/**
	 * Get the first view directory path from the application configuration.
	 */
	protected function viewPath(string $path = ''): string
	{
		$views = $this->lumis['config']['view.paths'][0] ?? resource_path('views');

		return $views . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}
}
