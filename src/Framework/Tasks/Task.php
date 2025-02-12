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

namespace MVPS\Lumis\Framework\Tasks;

use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Support\Logger;
use MVPS\Lumis\Framework\Support\Str;

abstract class Task extends Command
{
	/**
	 * The archive directory path relative to the task's root path.
	 *
	 * @var string
	 */
	protected string $archivePath = 'archive';

	/**
	 * The fully qualified path to the task's archive directory.
	 *
	 * @var string|null
	 */
	protected string|null $fullArchivePath = null;

	/**
	 * The fully qualified path to the task's log directory.
	 *
	 * @var string|null
	 */
	protected string|null $fullLogPath = null;

	/**
	 * The fully qualified path to the task's root directory.
	 *
	 * @var string|null
	 */
	protected string|null $fullTaskPath = null;

	/**
	 * The log directory path relative to the task's root path.
	 *
	 * @var string
	 */
	protected string $logPath = 'logs';

	/**
	 * The task log filename.
	 *
	 * @var string
	 */
	protected string $taskLogFile = 'task.log';

	/**
	 * The task run logger.
	 *
	 * @var \MVPS\Lumis\Framework\Support\Logger|null
	 */
	protected Logger|null $taskLogger = null;

	/**
	 * The task's name.
	 *
	 * @var string
	 */
	protected string $taskName = '';

	/**
	 * The task's root directory path.
	 *
	 * @var string
	 */
	protected string $taskPath = '';

	/**
	 * Create a new task instance.
	 */
	public function __construct(string|null $name = null)
	{
		parent::__construct($name);

		if ($this->taskName === '') {
			$this->setTaskName(self::generateTaskName());
		}
	}

	/**
	 * Moves the files in the log directory to the archive directory.
	 */
	public function archiveLogFiles(bool $hidden = false): void
	{
		$files = $this->taskLogger->files($this->taskLogger->getDirectory(), $hidden);

		if (empty($files)) {
			return;
		}

		$time = time();

		foreach ($files as $file) {
			$this->taskLogger->move(
				$file->getPathname(),
				$this->archivePath() . '/' . $time . '_' . $file->getFilename()
			);
		}
	}

	/**
	 * Get the task's archive path.
	 */
	public function archivePath(bool $relative = false): string
	{
		if ($relative) {
			return $this->formatPath($this->archivePath);
		}

		if (is_null($this->fullArchivePath)) {
			$this->setFullArchivePath();
		}

		return $this->fullArchivePath;
	}

	/**
	 * Removes files from the archive directory that are older than the
	 * given number of days.
	 */
	public function cleanArchiveDirectory(int $days = 14): void
	{
		$this->taskLogger->purgeFiles($this->archivePath(), $days);
	}

	/**
	 * Get the default namespace for application tasks.
	 */
	public static function defaultNamespace(string|null $rootNamespace = null): string
	{
		return rtrim(! is_null($rootNamespace) ? $rootNamespace : app()->getNamespace(), '\\') . '\Tasks';
	}

	/**
	 * Format a path value, such as the archive or log directory value. The
	 * formatted path removed the "/" character from the beginning of the path.
	 */
	protected function formatPath(string $path): string
	{
		return ltrim($path, '/');
	}

	/**
	 * Generates a formatted path with the provided paths.
	 */
	protected function formatPaths(array $paths): string
	{
		return implode(
			'/',
			array_map(fn ($path) => $this->formatPath($path), $paths)
		);
	}

	/**
	 * Generates a task name based on the provided name. If a name is not provided,
	 * the current class name will be used to generate the task name.
	 */
	public static function generateTaskName(string $name = ''): string
	{
		return static::generateTaskPath($name, true);
	}

	/**
	 * Generates a path relative to the application's 'tasks' based on the
	 * provided path. If a path is not provided, the task path will be generated
	 * from the current class namespace.
	 *
	 * If the optional base name parameter is set to true, the method returns
	 * only the base name of the path.
	 */
	public static function generateTaskPath(string $path = '', bool $baseName = false): string
	{
		// Create a formatted namespace by removing the root namespace for the
		// application's tasks and trimming any "task" references from the end
		// of the class name.
		$namespace =  Str::chopEnd(
			Str::chopStart($path ?: static::class, static::defaultNamespace() . '\\'),
			['Task', 'task', 'TASK']
		);

		$pathItems = collection(explode('\\', $namespace))
			->transform(fn ($item) => Str::snake($item));

		return $baseName ? $pathItems->last() : $pathItems->implode('/');
	}

	/**
	 * Determines whether the current task path is valid.
	 */
	protected function isValidTaskPath(): bool
	{
		return trim($this->taskPath) !== '';
	}

	/**
	 * Get the task's log path.
	 */
	public function logPath(bool $relative = false): string
	{
		if ($relative) {
			return $this->formatPath($this->logPath);
		}

		if (is_null($this->fullLogPath)) {
			$this->setFullLogPath();
		}

		return $this->fullLogPath;
	}

	/**
	 * Set the path to the task's archive directory. This must be a relative
	 * path from the task's root directory.
	 */
	public function setArchivePath(string $path): static
	{
		$this->archivePath = $this->formatPath($path);

		$this->setFullArchivePath();

		return $this;
	}

	/**
	 * Set the fully qualified path to the task's archive directory.
	 */
	public function setFullArchivePath(): static
	{
		$this->validateTaskPath();

		$this->fullArchivePath = task_path(
			$this->formatPaths([$this->taskPath, $this->archivePath])
		);

		return $this;
	}

	/**
	 * Set the fully qualified path to the task's log directory.
	 */
	public function setFullLogPath(): static
	{
		$this->validateTaskPath();

		$this->fullLogPath = task_path(
			$this->formatPaths([$this->taskPath, $this->logPath])
		);

		return $this;
	}

	/**
	 * Set the fully qualified path to the task's log directory.
	 */
	public function setFullTaskPath(): static
	{
		$this->validateTaskPath();

		$this->fullTaskPath = task_path($this->formatPath($this->taskPath));

		return $this;
	}

	/**
	 * Set the path to the task's log directory. This must be a relative path
	 * from the task's root directory.
	 */
	public function setLogPath(string $path): static
	{
		$this->logPath = $this->formatPath($path);

		$this->setFullLogPath();

		return $this;
	}

	/**
	 * Set the task's log filename.
	 */
	public function setTaskLogFile(string $filename): static
	{
		$this->taskLogFile = $filename;

		return $this;
	}

	/**
	 * Set the task's Logger instance.
	 */
	public function setTaskLogger(Logger $logger): static
	{
		$this->taskLogger = $logger;

		return $this;
	}

	/**
	 * Set the task's name.
	 */
	public function setTaskName(string $name): static
	{
		$this->taskName = $name;

		return $this;
	}

	/**
	 * Set the fully qualified path to the task's root directory.
	 */
	public function setTaskPath(string $path = ''): static
	{
		$this->taskPath = $this->formatPath($path ?: $this->taskPath);

		$this->setFullTaskPath();

		return $this;
	}

	/**
	 * Get the task's log file.
	 */
	public function taskLogFile(): string
	{
		return $this->taskLogFile;
	}

	/**
	 * Get the task's Logger instance.
	 */
	public function taskLogger(): Logger|null
	{
		return $this->taskLogger;
	}

	/**
	 * Get the task's name.
	 */
	public function taskName(): string
	{
		return $this->taskName;
	}

	/**
	 * Get the task's root path.
	 */
	public function taskPath(bool $relative = false): string
	{
		$this->validateTaskPath();

		if ($relative) {
			return $this->formatPath($this->taskPath);
		}

		if (is_null($this->fullTaskPath)) {
			$this->setFullTaskPath();
		}

		return $this->fullTaskPath;
	}

	/**
	 * Validates the task path and sets a default value if it is invalid.
	 */
	protected function validateTaskPath(): void
	{
		if (! $this->isValidTaskPath()) {
			$this->setTaskPath(self::generateTaskPath());
		}
	}

	/**
	 * Writes the provided message to the task log file.
	 *
	 * This method handles logging messages both to the task log file and
	 * optionally to the console. If console output is enabled, the message
	 * will be displayed in the specified style.
	 */
	public function writeTaskLog(
		string $message,
		bool $consoleOutput = true,
		string $consoleOutputStyle = 'line',
		string $consoleOutputDateFormat = '[Y-m-d H:i:s]'
	): int|bool {
		if ($consoleOutput) {
			$this->{$consoleOutputStyle}(sprintf('%s %s', date($consoleOutputDateFormat), $message));
		}

		return $this->taskLogger->write($message);
	}
}
