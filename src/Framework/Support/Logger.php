<?php

namespace MVPS\Lumis\Framework\Support;

use Carbon\Carbon;
use DirectoryIterator;

abstract class Logger
{
	/**
	 * The log directory.
	 *
	 * @var string
	 */
	protected string $directory;

	/**
	 * The log file.
	 *
	 * @var string
	 */
	protected string $logFile;

	/**
	 * Remove all files and sub-directories from a directory.
	 */
	public function clearDirectory(string $directory, bool $removeSubDirs = true): void
	{
		$dirPath = str_replace('\\', '/', $directory);
		$dirItems = glob(rtrim($dirPath, '/') . '/{*,.[!.]*,..?*}', GLOB_BRACE);

		foreach ($dirItems as $item) {
			if (is_dir($item)) {
				$this->clearDirectory($item, $removeSubDirs);

				if ($removeSubDirs) {
					rmdir($item);
				}
			} else {
				unlink($item);
			}
		}
	}

	/**
	 * Create a directory if it does not exist.
	 */
	public function createDirectory(string $directory): string
	{
		$dirNames = explode('/', rtrim(str_replace('\\', '/', $directory), '/'));
		$dirPath = '';

		foreach ($dirNames as $dirName) {
			$dirPath .= $dirName . '/';

			if ($dirPath && !is_dir($dirPath)) {
				mkdir($dirPath, 0755);
			}
		}

		return $dirPath;
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
	 * Move a file to another location.
	 */
	public function moveFile(string $from, string $to): bool
	{
		if (!file_exists($from)) {
			return false;
		}

		return rename($from, $to);
	}

	/**
	 * Purge files from a directory that have not been modified in number of days provided.
	 */
	public function purgeFiles(string $directory, int $purgeDays = 14): void
	{
		$dirIterator = new DirectoryIterator($directory);

		$now = Carbon::now();
		$now->subDays($purgeDays);

		foreach ($dirIterator as $file) {
			if (
				$file->isDot()
				|| !$file->isFile()
				|| str_starts_with($file->getFilename(), '.git')
				|| $file->getMTime() > $now->timestamp
			) {
				continue;
			}

			unlink($file->getPathname());
		}
	}

	/**
	 * Set the log directory. Creates the log directory if it does not exist.
	 */
	public function setDirectory(string $directory): self
	{
		$this->directory = rtrim($this->createDirectory($directory), '/') . '/';

		return $this;
	}

	/**
	 * Set the log file.
	 */
	public function setLogFile(string $logFile): self
	{
		$file = str_replace($this->directory, '', $logFile);

		$this->logFile = ltrim($file, '/');

		return $this;
	}
}
