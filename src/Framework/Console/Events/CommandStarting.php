<?php

namespace MVPS\Lumis\Framework\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandStarting
{
	/**
	 * The command name.
	 *
	 * @var string
	 */
	public string $command;

	/**
	 * The console input implementation.
	 *
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	public InputInterface $input;

	/**
	 * The command output implementation.
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	public OutputInterface $output;

	/**
	 * Create a new command starting event instance.
	 */
	public function __construct(string $command, InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;
		$this->command = $command;
	}
}
