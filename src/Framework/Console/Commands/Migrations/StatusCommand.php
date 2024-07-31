<?php

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use Illuminate\Database\Migrations\Migrator;
use MVPS\Lumis\Framework\Collections\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:status')]
class StatusCommand extends BaseCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Show the status of each migration';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:status';

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected Migrator $migrator;

	/**
	 * Create a new migrate status command instance.
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Get an array of all of the migration files.
	 */
	protected function getAllMigrationFiles(): array
	{
		return $this->migrator->getMigrationFiles($this->getMigrationPaths());
	}

	/**
	 * Get the migrate status command options.
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
				'pending',
				null,
				InputOption::VALUE_OPTIONAL,
				'Only list pending migrations',
				false,
			],
			[
				'path',
				null,
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'The path(s) to the migrations files to use',
			],
			[
				'realpath',
				null,
				InputOption::VALUE_NONE,
				'Indicate any provided migration file paths are pre-resolved absolute paths',
			],
		];
	}

	/**
	 * Get the status for the given run migrations.
	 */
	protected function getStatusFor(array $ran, array $batches): Collection
	{
		return Collection::make($this->getAllMigrationFiles())
			->map(function ($migration) use ($ran, $batches) {
				$migrationName = $this->migrator->getMigrationName($migration);

				$status = in_array($migrationName, $ran)
					? '<fg=green;options=bold>Ran</>'
					: '<fg=yellow;options=bold>Pending</>';

				if (in_array($migrationName, $ran)) {
					$status = '[' . $batches[$migrationName] . '] ' . $status;
				}

				return [$migrationName, $status];
			});
	}

	/**
	 * Execute the migrate status command.
	 */
	public function handle(): int|bool|null
	{
		return $this->migrator->usingConnection($this->option('database'), function () {
			if (! $this->migrator->repositoryExists()) {
				$this->components->error('Migration table not found.');

				return BaseCommand::FAILURE;
			}

			$ran = $this->migrator->getRepository()->getRan();

			$batches = $this->migrator->getRepository()->getMigrationBatches();

			$migrations = $this->getStatusFor($ran, $batches)
				->when(
					$this->option('pending') !== false,
					fn ($collection) => $collection->filter(function ($migration) {
						return str($migration[1])->contains('Pending');
					})
				);

			if (count($migrations) > 0) {
				$this->newLine();

				$this->components->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Batch / Status</>');

				$migrations->each(
					fn ($migration) => $this->components->twoColumnDetail($migration[0], $migration[1])
				);

				$this->newLine();
			} elseif ($this->option('pending') !== false) {
				$this->components->info('No pending migrations');
			} else {
				$this->components->info('No migrations found');
			}

			if ($this->option('pending') && $migrations->some(fn ($m) => str($m[1])->contains('Pending'))) {
				return $this->option('pending');
			}

			return BaseCommand::SUCCESS;
		});
	}
}
