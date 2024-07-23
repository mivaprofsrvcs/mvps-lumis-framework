<?php

namespace MVPS\Lumis\Framework\Cache\RateLimiting;

class GlobalLimit extends Limit
{
	/**
	 * Create a new global limit instance.
	 */
	public function __construct(int $maxAttempts, int $decaySeconds = 60)
	{
		parent::__construct('', $maxAttempts, $decaySeconds);
	}
}
