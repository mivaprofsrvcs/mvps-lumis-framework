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

namespace MVPS\Lumis\Framework\Console\Traits;

use Closure;

use function Laravel\Prompts\confirm;

trait ConfirmableTrait
{
	/**
	 * Confirm before proceeding with the action.
	 *
	 * This method only asks for confirmation in production.
	 */
	public function confirmToProceed(
		string $warning = 'Application In Production',
		Closure|bool|null $callback = null
	): bool {
		$callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

		$shouldConfirm = value($callback);

		if ($shouldConfirm) {
			if ($this->hasOption('force') && $this->option('force')) {
				return true;
			}

			$this->components->alert($warning);

			$confirmed = confirm('Are you sure you want to run this command?', default: false);

			if (! $confirmed) {
				$this->components->warn('Command cancelled.');

				return false;
			}
		}

		return true;
	}

	/**
	 * Get the default confirmation callback.
	 */
	protected function getDefaultConfirmCallback(): Closure
	{
		return function () {
			return $this->getLumis()->environment() === 'production';
		};
	}
}
