<?php

namespace MVPS\Lumis\Framework\Support;

use DirectoryIterator;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class Logger extends Filesystem
{
	/**
	 * The log directory.
	 *
	 * @var string
	 */
	protected string $directory = '';

	/**
	 * The log file.
	 *
	 * @var string
	 */
	protected string $logFile = '';

	/**
	 * Clear the contents of the log file.
	 */
	public function clearLogFile(): int|bool
	{
		return file_put_contents($this->getLogFilePath(), '');
	}

	/**
	 * Get the log directory.
	 */
	public function getDirectory(): string
	{
		return $this->directory;
	}

	/**
	 * Get the log file.
	 */
	public function getLogFile(): string
	{
		return $this->logFile;
	}

	/**
	 * Get the full log file path.
	 */
	public function getLogFilePath(): string
	{
		return $this->directory . $this->logFile;
	}

	/**
	 * Purge files from a directory that have not been modified in number of days provided.
	 */
	public function purgeFiles(string $directory, int $purgeDays = 14): void
	{
		$files = $this->files($directory);

		if (empty($files)) {
			return;
		}

		$now = now();
		$now->subDays($purgeDays);

		foreach ($files as $file) {
			if (str_starts_with($file->getFilename(), '.git') || $file->getMTime() > $now->timestamp) {
				continue;
			}

			$this->delete($file->getPathname());
		}
	}

	/**
	 * Set the log directory. Creates the log directory if it does not exist.
	 */
	public function setDirectory(string $directory): static
	{
		$this->directory = rtrim($this->createDirectory($directory), '/') . '/';

		return $this;
	}

	/**
	 * Set the log file.
	 */
	public function setLogFile(string $logFile): static
	{
		$file = str_replace($this->directory, '', $logFile);

		$this->logFile = ltrim($file, '/');

		return $this;
	}
}
