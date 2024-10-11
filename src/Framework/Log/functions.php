<?php

namespace MVPS\Lumis\Framework\Log;

if (! function_exists('MVPS\Lumis\Framework\Log\log')) {
	/**
	 * Log a debug message to the logs.
	 *
	 * @param  string|null  $message
	 * @param  array  $context
	 * @return ($message is null ? \MVPS\Lumis\Framework\Log\LogManager : null)
	 */
	function log(string|null $message = null, array $context = [])
	{
		return logger($message, $context);
	}
}
