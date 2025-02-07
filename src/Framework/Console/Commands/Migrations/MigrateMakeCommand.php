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

use Illuminate\Database\Console\Migrations\TableGuesser;
use MVPS\Lumis\Framework\Console\Commands\Migrations\MigrationCreator;
use MVPS\Lumis\Framework\Contracts\Console\PromptsForMissingInput;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:migration')]
class MigrateMakeCommand extends BaseCommand implements PromptsForMissingInput
{
	/**
	 * The migration creator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Console\Commands\Migrations\MigrationCreator
	 */
	protected MigrationCreator $creator;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create a new migration file';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'make:migration
		{name : The name of the migration}
		{--create= : The table to be created}
		{--table= : The table to migrate}
		{--path= : The location where the migration file should be created}
		{--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

	/**
	 * Create a new make migration command instance.
	 */
	public function __construct(MigrationCreator $creator)
	{
		parent::__construct();

		$this->creator = $creator;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function getMigrationPath(): string
	{
		$targetPath = $this->input->getOption('path');

		if (! is_null($targetPath)) {
			return ! $this->usingRealPath()
				? $this->lumis->basePath() . '/' . $targetPath
				: $targetPath;
		}

		return parent::getMigrationPath();
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): void
	{
		// Allows the developer to customize the schema operation by specifying
		// target tables and indicating whether to create tables from scratch
		// if they don't exist.
		$name = Str::snake(trim($this->input->getArgument('name')));

		$table = $this->input->getOption('table');

		$create = $this->input->getOption('create') ?: false;

		// If a table name is not explicitly provided but the 'create' option
		// is present, use the value of the 'create' option as the table name.
		// This allows for a concise way to specify both table creation and
		// data insertion in a single command.
		if (! $table && is_string($create)) {
			$table = $create;

			$create = true;
		}

		// If the migration name contains 'create', attempt to infer the table name.
		// This provides a shortcut for creating migrations for new tables.
		if (! $table) {
			[$table, $create] = TableGuesser::guess($name);
		}

		// Write the migration file to disk and then trigger a dump-autoload
		// to ensure the newly created migration class is registered by the
		// autoloader.
		$this->writeMigration($name, $table, $create);
	}

	/**
	 * Prompt for missing input arguments using the returned questions.
	 */
	protected function promptForMissingArgumentsUsing(): array
	{
		return [
			'name' => ['What should the migration be named?', 'E.g. create_products_table'],
		];
	}

	/**
	 * Write the migration file to disk.
	 */
	protected function writeMigration(string $name, string|null $table = null, bool $create = false): void
	{
		$file = $this->creator->create($name, $this->getMigrationPath(), $table, $create);

		$this->components->info(sprintf('Migration [%s] created successfully.', $file));
	}
}
