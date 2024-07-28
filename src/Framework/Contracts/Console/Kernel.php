<?php

namespace MVPS\Lumis\Framework\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Kernel
{
	/**
	 * Get all of the commands registered with the console.
	 */
	public function all(): array;

	/**
	 * Bootstrap the application for Lumis console commands.
	 */
	public function bootstrap(): void;

	/**
	 * Run a Lumis console command by name.
	 */
	public function call(string $command, array $parameters = [], OutputInterface|null $outputBuffer = null): int;

	/**
	 * Handle an incoming console command.
	 */
	public function handle(InputInterface $input, OutputInterface $output = null): int;

	/**
	 * Get the output for the last run command.
	 */
	public function output(): string;
}
