<?php

namespace MVPS\Lumis\Framework\Routing\Traits;

trait FiltersControllerMiddleware
{
	/**
	 * Determine if the given options exclude a particular method.
	 */
	public static function methodExcludedByOptions(string $method, array $options): bool
	{
		return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
			(! empty($options['except']) && in_array($method, (array) $options['except']));
	}
}
