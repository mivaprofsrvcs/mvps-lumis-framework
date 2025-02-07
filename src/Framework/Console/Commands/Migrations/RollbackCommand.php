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

use Illuminate\Database\Migrations\Migrator;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('migrate:rollback')]
class RollbackCommand extends BaseCommand
{
	use ConfirmableTrait;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Rollback the last database migration';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:rollback';

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected Migrator $migrator;

	/**
	 * Create a new migrate rollback command instance.
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Get the migrate rollback command options.
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
				InputOption::VALUE_NONE,
				'Dump the SQL queries that would be run',
			],
			[
				'step',
				null,
				InputOption::VALUE_OPTIONAL,
				'The number of migrations to be reverted',
			],
			[
				'batch',
				null,
				InputOption::VALUE_REQUIRED,
				'The batch of migrations (identified by their batch number) to be reverted',
			],
		];
	}

	/**
	 * Execute the migrate rollback command.
	 */
	public function handle(): int
	{
		if (! $this->confirmToProceed()) {
			return BaseCommand::FAILURE;
		}

		$this->migrator->usingConnection($this->option('database'), function () {
			$this->migrator->setOutput($this->output)
				->rollback($this->getMigrationPaths(), [
					'pretend' => $this->option('pretend'),
					'step' => (int) $this->option('step'),
					'batch' => (int) $this->option('batch'),
				]);
		});

		return BaseCommand::SUCCESS;
	}
}
