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

use Illuminate\Console\Prohibitable;
use Illuminate\Database\Migrations\Migrator;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:reset')]
class ResetCommand extends BaseCommand
{
	use ConfirmableTrait;
	use Prohibitable;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Rollback all database migrations';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:reset';

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected Migrator $migrator;

	/**
	 * Create a new migrate reset command instance.
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Get the migrate reset command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'database',
				null,
				InputOption::VALUE_OPTIONAL,
				'The database connection to use',
			],
			[
				'force',
				null,
				InputOption::VALUE_NONE,
				'Force the operation to run when in production',
			],
			[
				'path',
				null,
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'The path(s) to the migrations files to be executed',
			],
			[
				'realpath',
				null,
				InputOption::VALUE_NONE,
				'Indicate any provided migration file paths are pre-resolved absolute paths',
			],
			[
				'pretend',
				null,
				InputOption::VALUE_NONE, 'Dump the SQL queries that would be run',
			],
		];
	}

	/**
	 * Execute the migrate rest command.
	 */
	public function handle(): int
	{
		if ($this->isProhibited() || ! $this->confirmToProceed()) {
			return BaseCommand::FAILURE;
		}

		$this->migrator->usingConnection($this->option('database'), function () {
			// Ensure the migration table exists before attempting to rollback
			// and re-run  all migrations. If the table is absent, exit with an
			// informational message  for the developers.
			if (! $this->migrator->repositoryExists()) {
				return $this->components->warn('Migration table not found.');
			}

			$this->migrator->setOutput($this->output)
				->reset($this->getMigrationPaths(), $this->option('pretend'));
		});

		return BaseCommand::SUCCESS;
	}
}
