<?php

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
