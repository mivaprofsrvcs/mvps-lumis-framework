<?php

namespace MVPS\Lumis\Framework;

use Closure;

class EnvironmentDetector
{
	/**
	 * Detect the application's current environment.
	 */
	public function detect(Closure $callback, array|null $consoleArgs = null): string
	{
		if ($consoleArgs) {
			return $this->detectConsoleEnvironment($callback, $consoleArgs);
		}

		return $this->detectWebEnvironment($callback);
	}

	/**
	 * Set the application environment from command-line arguments.
	 */
	protected function detectConsoleEnvironment(Closure $callback, array $args): string
	{
		// Check if an environment argument was passed via console arguments.
		// If present, it overrides the default environment. If not, treat the
		// request as a typical HTTP "web" request to determine the environment.
		if (! is_null($value = $this->getEnvironmentArgument($args))) {
			return $value;
		}

		return $this->detectWebEnvironment($callback);
	}

	/**
	 * Set the application environment for a web request.
	 */
	protected function detectWebEnvironment(Closure $callback): string
	{
		return $callback();
	}

	/**
	 * Get the environment argument from the console.
	 */
	protected function getEnvironmentArgument(array $args): string|null
	{
		foreach ($args as $i => $value) {
			if ($value === '--env') {
				return $args[$i + 1] ?? null;
			}

			if (str_starts_with($value, '--env')) {
				return head(array_slice(explode('=', $value), 1));
			}
		}

		return null;
	}
}
