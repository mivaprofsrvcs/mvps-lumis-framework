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
