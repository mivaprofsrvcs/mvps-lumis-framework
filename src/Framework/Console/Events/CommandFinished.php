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
