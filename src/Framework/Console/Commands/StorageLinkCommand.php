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

#[AsCommand(name: 'storage:link')]
class StorageLinkCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Create the symbolic links configured for the application';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'storage:link
		{--relative : Create the symbolic link using relative paths}
		{--force : Recreate existing symbolic links}';

	/**
	 * Execute the storage link command.
	 */
	public function handle(): void
	{
		$relative = $this->option('relative');
		$force = (bool) $this->option('force');

		foreach ($this->links() as $link => $target) {
			if (file_exists($link) && ! $this->isRemovableSymlink($link, $force)) {
				$this->components->error("The [$link] link already exists.");

				continue;
			}

			if (is_link($link)) {
				$this->lumis->make('files')->delete($link);
			}

			if ($relative) {
				$this->lumis->make('files')->relativeLink($target, $link);
			} else {
				$this->lumis->make('files')->link($target, $link);
			}

			$this->components->info("The [$link] link has been connected to [$target].");
		}
	}

	/**
	 * Determine if the provided path is a symlink that can be removed.
	 */
	protected function isRemovableSymlink(string $link, bool $force): bool
	{
		return is_link($link) && $force;
	}

	/**
	 * Get the symbolic links that are configured for the application.
	 */
	protected function links(): array
	{
		return $this->lumis['config']['filesystems.links'] ??
			[public_path('storage') => storage_path('app/public')];
	}
}
