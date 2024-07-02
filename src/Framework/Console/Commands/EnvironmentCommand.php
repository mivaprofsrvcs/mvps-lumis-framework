<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env')]
class EnvironmentCommand extends Command
{
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $description = 'Display the current framework environment';

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $name = 'env';

	/**
	 * Execute the console command.
	 */
	public function handle(): void
	{
		$this->components->info('The application environment is [' . $this->lumis['env'] . '].');
	}
}
