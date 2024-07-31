<?php

namespace MVPS\Lumis\Framework\Console\Commands\Seeds;

use Illuminate\Database\ConnectionResolverInterface as ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Console\Traits\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends Command
{
	use ConfirmableTrait;

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Seed the database with records';

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'db:seed';

	/**
	 * The connection resolver instance.
	 *
	 * @var \Illuminate\Database\ConnectionResolverInterface
	 */
	protected ConnectionResolver $resolver;

	/**
	 * Create a new database seed command instance.
	 */
	public function __construct(ConnectionResolver $resolver)
	{
		parent::__construct();

		$this->resolver = $resolver;
	}

	/**
	 * Get the database seed command arguments.
	 */
	protected function getArguments(): array
	{
		return [
			[
				'class',
				InputArgument::OPTIONAL,
				'The class name of the root seeder',
				null,
			],
		];
	}

	/**
	 * Get the name of the database connection to use.
	 */
	protected function getDatabase(): string
	{
		$database = $this->input->getOption('database');

		return $database ?: $this->lumis['config']['database.default'];
	}


	/**
	 * Get the database seed command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'class',
				null,
				InputOption::VALUE_OPTIONAL,
				'The class name of the root seeder',
				'Database\\Seeders\\DatabaseSeeder',
			],
			[
				'database',
				null,
				InputOption::VALUE_OPTIONAL,
				'The database connection to seed',
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
	 * Get a seeder instance from the container.
	 */
	protected function getSeeder(): Seeder
	{
		$class = $this->input->getArgument('class') ?? $this->input->getOption('class');

		if (! str_contains($class, '\\')) {
			$class = 'Database\\Seeders\\' . $class;
		}

		if ($class === 'Database\\Seeders\\DatabaseSeeder' && ! class_exists($class)) {
			$class = 'DatabaseSeeder';
		}

		return $this->lumis->make($class)
			->setContainer($this->lumis)
			->setCommand($this);
	}

	/**
	 * Execute the database seed command.
	 */
	public function handle(): int
	{
		if (! $this->confirmToProceed()) {
			return Command::FAILURE;
		}

		$this->components->info('Seeding database.');

		$previousConnection = $this->resolver->getDefaultConnection();

		$this->resolver->setDefaultConnection($this->getDatabase());

		Model::unguarded(fn () => $this->getSeeder()->__invoke());

		if ($previousConnection) {
			$this->resolver->setDefaultConnection($previousConnection);
		}

		return Command::SUCCESS;
	}
}
