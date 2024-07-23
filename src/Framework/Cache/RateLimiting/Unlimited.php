<?php

namespace MVPS\Lumis\Framework\Cache\RateLimiting;

class Unlimited extends GlobalLimit
{
	/**
	 * Create a new unlimited limit instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(PHP_INT_MAX);
	}
}
