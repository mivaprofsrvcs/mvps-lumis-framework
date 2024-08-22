<?php

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use Illuminate\Console\Prohibitable;
use Illuminate\Database\Events\DatabaseRefreshed;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:refresh')]
class RefreshCommand extends Command
{
	use ConfirmableTrait;
	use Prohibitable;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Reset and re-run all migrations';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:refresh';

	/**
	 * Get the migrate refresh command options.
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
				InputOption::VALUE_OPTIONAL,
				'The number of migrations to be reverted & re-run',
			],
		];
	}

	/**
	 * Execute the migrate refresh command.
	 */
	public function handle(): int
	{
		if ($this->isProhibited() || ! $this->confirmToProceed()) {
			return Command::FAILURE;
		}

		// Gather the options needed to run the command, including the database
		// to use and the migration path. Then, execute the command.
		$database = $this->input->getOption('database') ?? '';

		$path = $this->input->getOption('path');

		// If the "step" option is specified, it means we only want to rollback
		// a limited number of migrations before migrating again. For example,
		// the user might choose to rollback and remigrate the latest four
		// migrations instead of all.
		$step = $this->input->getOption('step') ?: 0;

		if ($step > 0) {
			$this->runRollback($database, $path, $step);
		} else {
			$this->runReset($database, $path);
		}

		// The refresh command aggregates several migration commands, providing
		// a convenient wrapper to execute them in succession. It also checks
		// if the database needs to be re-seeded.
		$this->call('migrate', array_filter([
			'--database' => $database,
			'--path' => $path,
			'--realpath' => $this->input->getOption('realpath'),
			'--force' => true,
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
	 * Run the reset command.
	 */
	protected function runReset(string $database, string|array $path): void
	{
		$this->call('migrate:reset', array_filter([
			'--database' => $database,
			'--path' => $path,
			'--realpath' => $this->input->getOption('realpath'),
			'--force' => true,
		]));
	}

	/**
	 * Run the rollback command.
	 */
	protected function runRollback(string $database, string|array $path, int $step): void
	{
		$this->call('migrate:rollback', array_filter([
			'--database' => $database,
			'--path' => $path,
			'--realpath' => $this->input->getOption('realpath'),
			'--step' => $step,
			'--force' => true,
		]));
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
