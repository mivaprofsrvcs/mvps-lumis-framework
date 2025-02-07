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
use MVPS\Lumis\Framework\Events\EventServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:generate')]
class EventGenerateCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Generate the missing events and listeners based on registration';

	/**
	 * Indicates whether the command should be shown in the Lumis command list.
	 *
	 * @var bool
	 */
	protected $hidden = true;

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'event:generate';

	/**
	 * Execute the event generate command.
	 */
	public function handle(): void
	{
		$providers = $this->lumis->getProviders(EventServiceProvider::class);

		foreach ($providers as $provider) {
			foreach ($provider->listens() as $event => $listeners) {
				$this->makeEventAndListeners($event, $listeners);
			}
		}

		$this->components->info('Events and listeners generated successfully.');
	}

	/**
	 * Make the event and listeners for the given event.
	 */
	protected function makeEventAndListeners(string $event, array $listeners): void
	{
		if (! str_contains($event, '\\')) {
			return;
		}

		$this->callSilent('make:event', ['name' => $event]);

		$this->makeListeners($event, $listeners);
	}

	/**
	 * Make the listeners for the given event.
	 */
	protected function makeListeners(string $event, array $listeners): void
	{
		foreach ($listeners as $listener) {
			$listener = preg_replace('/@.+$/', '', $listener);

			$this->callSilent(
				'make:listener',
				array_filter(['name' => $listener, '--event' => $event])
			);
		}
	}
}
