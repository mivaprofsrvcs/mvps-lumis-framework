<?php

namespace MVPS\Lumis\Framework\Errors;

use Error;
use ReflectionProperty;

class FatalError extends Error
{
	/**
	 * Create a new fatal error instance.
	 */
	public function __construct(
		string $message,
		int $code,
		private array $error,
		int|null $traceOffset = null,
		bool $traceArgs = true,
		array|null $trace = null,
	) {
		parent::__construct($message, $code);

		if (! is_null($trace)) {
			if (! $traceArgs) {
				foreach ($trace as &$frame) {
					unset($frame['args'], $frame['this'], $frame);
				}
			}
		} elseif (! is_null($traceOffset)) {
			if (
				function_exists('xdebug_get_function_stack') &&
				in_array(ini_get('xdebug.mode'), ['develop', false], true) &&
				$trace = @xdebug_get_function_stack()
			) {
				if ($traceOffset > 0) {
					array_splice($trace, -$traceOffset);
				}

				foreach ($trace as &$frame) {
					if (!isset($frame['type'])) {
						// XDebug pre 2.1.1 doesn't set the call type key - http://bugs.xdebug.org/view.php?id=695
						if (isset($frame['class'])) {
							$frame['type'] = '::';
						}
					} elseif ($frame['type'] === 'dynamic') {
						$frame['type'] = '->';
					} elseif ($frame['type'] === 'static') {
						$frame['type'] = '::';
					}

					// XDebug also has a different name for the parameters array
					if (! $traceArgs) {
						unset($frame['params'], $frame['args']);
					} elseif (isset($frame['params']) && ! isset($frame['args'])) {
						$frame['args'] = $frame['params'];

						unset($frame['params']);
					}
				}

				unset($frame);

				$trace = array_reverse($trace);
			} else {
				$trace = [];
			}
		}

		foreach (
			[
				'file' => $error['file'],
				'line' => $error['line'],
				'trace' => $trace,
			] as $property => $value
		) {
			if (! is_null($value)) {
				$refl = new ReflectionProperty(Error::class, $property);

				$refl->setValue($this, $value);
			}
		}
	}

	/**
	 * Get the error.
	 */
	public function getError(): array
	{
		return $this->error;
	}
}
