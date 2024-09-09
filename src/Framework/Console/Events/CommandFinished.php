<?php

namespace MVPS\Lumis\Framework\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandFinished
{
	/**
	 * The command name.
	 *
	 * @var string
	 */
	public string $command;

	/**
	 * The command exit code.
	 *
	 * @var int
	 */
	public int $exitCode;

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
	 * Create a new command finished event instance.
	 */
	public function __construct(string $command, InputInterface $input, OutputInterface $output, int $exitCode)
	{
		$this->input = $input;
		$this->output = $output;
		$this->command = $command;
		$this->exitCode = $exitCode;
	}
}
