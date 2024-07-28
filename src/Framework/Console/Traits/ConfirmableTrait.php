<?php

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
