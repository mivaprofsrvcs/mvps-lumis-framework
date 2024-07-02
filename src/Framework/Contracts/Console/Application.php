<?php

namespace MVPS\Lumis\Framework\Contracts\Console;

use Symfony\Component\Console\Output\OutputInterface;

interface Application
{
	/**
	 * Run a Lumis console command by name.
	 */
	public function call(string $command, array $parameters = [], OutputInterface|null $outputBuffer = null): int;

	/**
	 * Get the output from the last command.
	 */
	public function output(): string;
}
