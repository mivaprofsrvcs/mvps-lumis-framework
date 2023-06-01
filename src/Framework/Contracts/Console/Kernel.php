<?php

namespace MVPS\Lumis\Framework\Contracts\Console;

interface Kernel
{
	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void;

	/**
	 * Handle a console command.
	 */
	public function handle(): void;
}
