<?php

namespace MVPS\Lumis\Framework\Log;

if (! function_exists('MVPS\Lumis\Framework\Log\log')) {
	/**
	 * Log a debug message to the logs.
	 *
	 * @param  string|null  $message
	 * @param  array  $context
	 * @return ($message is null ? \Illuminate\Log\LogManager : null)
	 */
	function log(string|null $message = null, array $context = []): LogManager|null
	{
		return logger($message, $context);
	}
}
