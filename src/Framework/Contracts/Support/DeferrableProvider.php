<?php

namespace MVPS\Lumis\Framework\Contracts\Support;

interface DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array;
}
