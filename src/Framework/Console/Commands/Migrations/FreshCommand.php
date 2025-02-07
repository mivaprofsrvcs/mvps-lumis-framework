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
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Migrations\Migrator;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:fresh')]
class FreshCommand extends Command
{
	use ConfirmableTrait;
	use Prohibitable;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Drop all tables and re-run all migrations';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:fresh';

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected Migrator $migrator;

	/**
	 * Create a new migrate fresh command instance.
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Get the migrate fresh command options.
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
				'schema-path',
				null,
				InputOption::VALUE_OPTIONAL,
				'The path to a schema dump file',
			],
			[
				'seed',
				null,
				InputOption::VALUE_NONE,
				'Indicates if the seed task should be re-run',
			],
			[
				'seeder',
				null,
				InputOption::VALUE_OPTIONAL,
				'The class name of the root seeder',
			],
			[
				'step',
				null,
				InputOption::VALUE_NONE,
				'Force the migrations to be run so they can be rolled back individually',
			],
		];
	}

	/**
	 * Execute the migrate fresh command.
	 */
	public function handle(): int
	{
		if ($this->isProhibited() || ! $this->confirmToProceed()) {
			return Command::FAILURE;
		}

		$database = $this->input->getOption('database');

		$this->migrator->usingConnection($database, function () use ($database) {
			if ($this->migrator->repositoryExists()) {
				$this->newLine();

				$this->components->task(
					'Dropping all tables',
					fn () => (int) $this->callSilent('db:wipe', array_filter([
						'--database' => $database,
						'--drop-views' => $this->option('drop-views'),
						'--drop-types' => $this->option('drop-types'),
						'--force' => true,
					])) === 0
				);
			}
		});

		$this->newLine();

		$this->call('migrate', array_filter([
			'--database' => $database,
			'--path' => $this->input->getOption('path'),
			'--realpath' => $this->input->getOption('realpath'),
			'--schema-path' => $this->input->getOption('schema-path'),
			'--force' => true,
			'--step' => $this->option('step'),
		]));

		if ($this->lumis->bound(Dispatcher::class)) {
			$this->lumis[Dispatcher::class]->dispatch(
				new DatabaseRefreshed($database, $this->needsSeeding())
			);
		}

		if ($this->needsSeeding()) {
			$this->runSeeder($database);
		}

		return Command::SUCCESS;
	}

	/**
	 * Checks if database seeding is requested via command options.
	 */
	protected function needsSeeding(): bool
	{
		return $this->option('seed') || $this->option('seeder');
	}

	/**
	 * Run the database seeder command.
	 */
	protected function runSeeder(string|null $database = null): void
	{
		$this->call('db:seed', array_filter([
			'--database' => $database,
			'--class' => $this->option('seeder') ?: 'Database\\Seeders\\DatabaseSeeder',
			'--force' => true,
		]));
	}
}
