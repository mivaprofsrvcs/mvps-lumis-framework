<?php

namespace MVPS\Lumis\Framework\Cache\RateLimiting;

class Unlimited extends GlobalLimit
{
	/**
	 * Create a new unlimited limit instance.
	 */
	public function __construct()
	{
		parent::__construct(PHP_INT_MAX);
	}
}
