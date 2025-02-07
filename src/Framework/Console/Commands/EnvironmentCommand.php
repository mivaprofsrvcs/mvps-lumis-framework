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

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env')]
class EnvironmentCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Display the current framework environment';

	/**
	 * {@inheritdoc}
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
