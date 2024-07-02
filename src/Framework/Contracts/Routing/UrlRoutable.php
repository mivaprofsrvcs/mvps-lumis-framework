<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

interface UrlRoutable
{
	/**
	 * Get the value of the model's route key.
	 */
	public function getRouteKey(): mixed;

	/**
	 * Get the route key for the model.
	 */
	public function getRouteKeyName(): string;
}
