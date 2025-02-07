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

namespace MVPS\Lumis\Framework\Console\Commands;

use Illuminate\Console\Prohibitable;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:wipe')]
class WipeCommand extends Command
{
	use ConfirmableTrait;
	use Prohibitable;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Drop all tables, views, and types';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'db:wipe';

	/**
	 * Drop all of the database tables.
	 */
	protected function dropAllTables(string|null $database = null): void
	{
		$this->lumis['db']->connection($database)
			->getSchemaBuilder()
			->dropAllTables();
	}

	/**
	 * Drop all of the database types.
	 */
	protected function dropAllTypes(string|null $database = null): void
	{
		$this->lumis['db']->connection($database)
			->getSchemaBuilder()
			->dropAllTypes();
	}

	/**
	 * Drop all of the database views.
	 */
	protected function dropAllViews(string|null $database = null): void
	{
		$this->lumis['db']->connection($database)
			->getSchemaBuilder()
			->dropAllViews();
	}

	/**
	 * Get the database wipe command options.
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
				'drop-views',
				null,
				InputOption::VALUE_NONE,
				'Drop all tables and views',
			],
			[
				'drop-types',
				null,
				InputOption::VALUE_NONE,
				'Drop all tables and types (Postgres only)',
			],
			[
				'force',
				null,
				InputOption::VALUE_NONE,
				'Force the operation to run when in production',
			],
		];
	}

	/**
	 * Execute the database wipe command.
	 */
	public function handle(): int
	{
		if ($this->isProhibited() || ! $this->confirmToProceed()) {
			return Command::FAILURE;
		}

		$database = $this->input->getOption('database');

		if ($this->option('drop-views')) {
			$this->dropAllViews($database);

			$this->components->info('Dropped all views successfully.');
		}

		$this->dropAllTables($database);

		$this->components->info('Dropped all tables successfully.');

		if ($this->option('drop-types')) {
			$this->dropAllTypes($database);

			$this->components->info('Dropped all types successfully.');
		}

		return Command::SUCCESS;
	}
}
