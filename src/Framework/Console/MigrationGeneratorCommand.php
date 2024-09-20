<?php

namespace MVPS\Lumis\Framework\Console;

use MVPS\Lumis\Framework\Filesystem\Filesystem;

abstract class MigrationGeneratorCommand extends Command
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected Filesystem $files;

	/**
	 * Create a new migration generator command instance.
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Create a base migration file for the table.
	 */
	protected function createBaseMigration(string $table): string
	{
		return $this->lumis['migration.creator']->create(
			'create_' . $table . '_table',
			$this->lumis->databasePath('/migrations')
		);
	}

	/**
	 * Execute the migration generator command.
	 */
	public function handle(): int
	{
		$table = $this->migrationTableName();

		if ($this->migrationExists($table)) {
			$this->components->error('Migration already exists.');

			return Command::FAILURE;
		}

		$this->replaceMigrationPlaceholders($this->createBaseMigration($table), $table);

		$this->components->info('Migration created successfully.');

		return Command::SUCCESS;
	}

	/**
	 * Determine whether a migration for the table already exists.
	 */
	protected function migrationExists(string $table): bool
	{
		return count($this->files->glob(
			join_paths($this->lumis->databasePath('migrations'), '*_*_*_*_create_' . $table . '_table.php')
		)) !== 0;
	}

	/**
	 * Get the path to the migration stub file.
	 */
	abstract protected function migrationStubFile(): string;

	/**
	 * Get the migration table name.
	 */
	abstract protected function migrationTableName(): string;

	/**
	 * Replace the placeholders in the generated migration file.
	 */
	protected function replaceMigrationPlaceholders(string $path, string $table): void
	{
		$stub = str_replace(
			'{{table}}',
			$table,
			$this->files->get($this->migrationStubFile())
		);

		$this->files->put($path, $stub);
	}
}
