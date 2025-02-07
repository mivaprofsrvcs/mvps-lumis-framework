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

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'trans:publish')]
class TransPublishCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Publish all translation files that are available for customization';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'trans:publish
		{--existing : Publish and overwrite only the files that have already been published}
		{--force : Overwrite any existing files}';

	/**
	 * Execute the publish translations command.
	 */
	public function handle(): void
	{
		$transPath = $this->lumis->basePath('translations');

		if (! is_dir($transPath)) {
			(new Filesystem)->makeDirectory($transPath, recursive: true);
		}

		$basePath = Application::FRAMEWORK_PATH;

		$stubs = [
			// realpath($basePath . '/Translation/translations/auth.php') => 'auth.php',
			// realpath($basePath . '/Translation/translations/pagination.php') => 'pagination.php',
			// realpath($basePath . '/Translation/translations/passwords.php') => 'passwords.php',
			realpath($basePath . '/Translation/translations/validation.php') => 'validation.php',
		];

		foreach ($stubs as $from => $to) {
			$to = $transPath . DIRECTORY_SEPARATOR . ltrim($to, DIRECTORY_SEPARATOR);

			if (
				(! $this->option('existing') && (! file_exists($to) || $this->option('force'))) ||
				($this->option('existing') && file_exists($to))
			) {
				file_put_contents($to, file_get_contents($from));
			}
		}

		$this->components->info('Translation files published successfully.');
	}
}
