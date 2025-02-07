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

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use MVPS\Lumis\Framework\Console\Command;

class BaseCommand extends Command
{
	/**
	 * Get the path to the migration directory.
	 */
	protected function getMigrationPath(): string
	{
		return $this->lumis->databasePath() . DIRECTORY_SEPARATOR . 'migrations';
	}

	/**
	 * Get all of the migration paths.
	 */
	protected function getMigrationPaths(): array
	{
		// Checks if a custom database path has been defined. If so, uses the
		// path relative to the installation root to allow database migrations
		// from any custom directory.
		if ($this->input->hasOption('path') && $this->option('path')) {
			return collection($this->option('path'))
				->map(function ($path) {
					return ! $this->usingRealPath()
						? $this->lumis->basePath() . '/' . $path
						: $path;
				})
				->all();
		}

		return array_merge($this->migrator->paths(), [$this->getMigrationPath()]);
	}

	/**
	 * Determine if the given path(s) are pre-resolved "real" paths.
	 */
	protected function usingRealPath(): bool
	{
		return $this->input->hasOption('realpath') && $this->option('realpath');
	}
}
