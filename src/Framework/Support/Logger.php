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

namespace MVPS\Lumis\Framework\Support;

use MVPS\Lumis\Framework\Filesystem\Filesystem;

abstract class Logger extends Filesystem
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
		$this->directory = rtrim($directory, '/') . '/';

		$this->ensureDirectoryExists($directory);

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

	/**
	 * Write the contents to the log file.
	 */
	public function writeLog(mixed $contents, bool $append = false): int|bool
	{
		$writeMethod = $append ? 'append' : 'put';

		return $this->{$writeMethod}($this->getLogFilePath(), $contents);
	}
}
