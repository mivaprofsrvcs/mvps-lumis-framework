<?php

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use MVPS\Lumis\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:install')]
class InstallCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create the migration repository';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'migrate:install';

	/**
	 * The repository instance.
	 *
	 * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
	 */
	protected MigrationRepositoryInterface $repository;

	/**
	 * Create a new migrate install command instance.
	 */
	public function __construct(MigrationRepositoryInterface $repository)
	{
		parent::__construct();

		$this->repository = $repository;
	}

	/**
	 * Execute the migrate install command.
	 */
	public function handle(): void
	{
		$this->repository->setSource($this->input->getOption('database'));

		$this->repository->createRepository();

		$this->components->info('Migration table created successfully.');
	}

	/**
	 * Get the migrate install command options.
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
		];
	}
}
