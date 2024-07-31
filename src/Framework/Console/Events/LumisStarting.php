<?php

namespace MVPS\Lumis\Framework\Console\Events;

use MVPS\Lumis\Framework\Console\Application as ConsoleApplication;

class LumisStarting
{
	/**
	 * The Lumis console application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Console\Application
	 */
	public ConsoleApplication $lumis;

	/**
	 * Create a new Lumis starting event instance.
	 */
	public function __construct(ConsoleApplication $lumis)
	{
		$this->lumis = $lumis;
	}
}
