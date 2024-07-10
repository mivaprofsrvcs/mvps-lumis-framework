<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\GeneratorCommand;
use MVPS\Lumis\Framework\Contracts\Console\PromptsForMissingInput as PromptsForMissingInputContract;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Tasks\Task;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\text;

#[AsCommand(name: 'make:task')]
class TaskMakeCommand extends GeneratorCommand
{
	/**
	 * The archive path input value.
	 *
	 * @var string|null
	 */
	protected string|null $archivePath = null;

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $description = 'Create a new Task class';

	/**
	 * The log path input value.
	 *
	 * @var string|null
	 */
	protected string|null $logPath = null;

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $name = 'make:task';

	/**
	 * Array mapping option names to their corresponding input option names.
	 *
	 * @var array
	 */
	protected array $options = [
		'archive_path' => 'archive-dir',
		'force' => 'force',
		'log_path' => 'log-dir',
		'make_paths' => 'make-dirs',
		'no_task_path' => 'no-dir',
		'task_path' => 'dir',
	];

	/**
	 * The task path input value.
	 *
	 * @var string|null
	 */
	protected string|null $taskPath = null;

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected string $type = 'Task';

	/**
	 * Interact further with the user if they were prompted for missing arguments.
	 */
	protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
	{
		if ($this->didReceiveOptions($input) || (bool) $input->getOption($this->options['no_task_path'])) {
			return;
		}

		$taskPath = $this->generateTaskPathFromName($input->getArgument('name'));

		$taskDir = text(
			label: 'What is the task directory path?',
			placeholder: 'E.g. ' . $taskPath,
			default: $taskPath,
			hint: "The path must be relative to the root 'tasks' directory."
		);

		$input->setOption($this->options['task_path'], $this->trimPath($taskDir));

		$logDirDefault = $input->getOption($this->options['log_path']) ?: 'logs';

		$logDir = text(
			label: "What is the task's log directory path?",
			placeholder: 'E.g. ' . $logDirDefault,
			default: $logDirDefault,
			hint: 'The path must be relative to the task directory path.'
		);

		$input->setOption($this->options['log_path'], $this->trimPath($logDir));

		$archiveDirDefault = $input->getOption($this->options['archive_path']) ?: 'archive';

		$archiveDir = text(
			label: "What is the task's archive directory path?",
			placeholder: 'E.g. ' . $archiveDirDefault,
			default: $archiveDirDefault,
			hint: 'The path must be relative to the task directory path.'
		);

		$input->setOption($this->options['archive_path'], $this->trimPath($archiveDir));
	}

	/**
	 * Build the class with the given name.
	 */
	protected function buildClass(string $name): string
	{
		$class = parent::buildClass($name);

		$taskName = Task::generateTaskName($name);

		$class = $this->replaceArchivePath($class);

		$class = $this->replaceLogPath($class);

		$class = $this->replaceCommand($class, $taskName);

		$class = $this->replaceTaskName($class, $taskName);

		$class = $this->replaceTaskPath($class);

		return $class;
	}

	/**
	 * Generates a task path with the provided name.
	 */
	protected function generateTaskPathFromName(string $name): string
	{
		return Task::generateTaskPath(str_replace('/', '\\', $name));
	}

	/**
	 * Get the default namespace for the class.
	 */
	protected function getDefaultNamespace(string $rootNamespace): string
	{
		return $rootNamespace . '\Tasks';
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				$this->options['no_task_path'],
				'nd',
				InputOption::VALUE_NONE,
				'Exclude the generation of a task directory path for your Task.',
			],
			[
				$this->options['make_paths'],
				'md',
				InputOption::VALUE_NONE,
				'Make all task directories for your Task.',
			],
			[
				$this->options['task_path'],
				'd',
				InputOption::VALUE_REQUIRED,
				"The task directory path relative to the root 'tasks' directory.",
			],
			[
				$this->options['log_path'],
				'l',
				InputOption::VALUE_REQUIRED,
				"The task log directory path relative to the task directory.",
			],
			[
				$this->options['archive_path'],
				'a',
				InputOption::VALUE_REQUIRED,
				"The task archive directory path relative to the task directory.",
			],
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Create the class even if the task already exists',
			],
		];
	}

	/**
	 * Get the stub file for the generator.
	 */
	protected function getStub(): string
	{
		$relativePath = '/stubs/task.stub';
		$customPath = $this->lumis->basePath(trim($relativePath, '/'));

		return file_exists($customPath)
			? $customPath
			: __DIR__ . $relativePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\Filesystem\FileNotFoundException
	 */
	public function handle(): bool|null
	{
		parent::handle();

		if ((bool) $this->input->getOption($this->options['no_task_path']) || (string) $this->taskPath === '') {
			return null;
		}

		$this->writeTaskPaths(
			fn () => $this->components->info('Task paths created successfully.')
		);

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		parent::interact($input, $output);

		if ($this instanceof PromptsForMissingInputContract) {
			$this->promptForMissingArguments($input, $output);
		}

		if ((bool) $input->getOption($this->options['no_task_path'])) {
			return;
		}

		if ((bool) $this->input->getOption($this->options['make_paths'])) {
			$this->taskPath = $this->generateTaskPathFromName($this->input->getArgument('name'));
			$this->logPath = 'logs';
			$this->archivePath = 'archive';
		} else {
			$this->taskPath = $this->trimPath((string) $this->input->getOption($this->options['task_path']));
			$this->logPath = $this->trimPath((string) $this->input->getOption($this->options['log_path']));
			$this->archivePath = $this->trimPath((string) $this->input->getOption($this->options['archive_path']));
		}
	}

	/**
	 * Replace the archive path for the given stub.
	 */
	protected function replaceArchivePath(string $stub): string
	{
		return str_replace('{{ archivePath }}', (string) $this->archivePath, $stub);
	}

	/**
	 * Replace the task console command for the given stub.
	 */
	protected function replaceCommand(string $stub, string $taskName): string
	{
		return str_replace(
			'{{ command }}',
			'task:' . Str::kebab(Str::studly($taskName)),
			$stub
		);
	}

	/**
	 * Replace the log path for the given stub.
	 */
	protected function replaceLogPath(string $stub): string
	{
		return str_replace('{{ logPath }}', (string) $this->logPath, $stub);
	}

	/**
	 * Replace the task name for the given stub.
	 */
	protected function replaceTaskName(string $stub, string $taskName): string
	{
		return str_replace('{{ taskName }}', $taskName, $stub);
	}

	/**
	 * Replace the task path for the given stub.
	 */
	protected function replaceTaskPath(string $stub): string
	{
		return str_replace('{{ taskPath }}', (string) $this->taskPath, $stub);
	}

	/**
	 * Strips whitespace and '/' characters from the beginning and end of the
	 * provided path.
	 */
	protected function trimPath(string $path)
	{
		return trim($path, " \n\r\t\v\x00/");
	}

	/**
	 * Create the directory paths for the task.
	 */
	protected function writeTaskPaths(callable|null $onSuccess = null): void
	{
		$rootPath = task_path($this->taskPath);

		if (! $this->files->isDirectory($rootPath)) {
			$this->files->makeDirectory($rootPath, 0777, true, true);
		}

		if ((string) $this->logPath !== '') {
			$fullLogPath = $rootPath . '/' . $this->logPath;

			if (! $this->files->isDirectory($fullLogPath)) {
				$this->files->makeDirectory($fullLogPath, 0777, true, true);
			}
		}

		if ((string) $this->archivePath !== '') {
			$fullArchivePath = $rootPath . '/' . $this->archivePath;

			if (! $this->files->isDirectory($fullArchivePath)) {
				$this->files->makeDirectory($fullArchivePath, 0777, true, true);
			}
		}

		if (! is_null($onSuccess)) {
			$onSuccess();
		}
	}
}
