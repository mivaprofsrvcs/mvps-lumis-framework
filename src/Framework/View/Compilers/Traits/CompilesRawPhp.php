<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesRawPhp
{
	/**
	 * Compile the raw PHP statements into valid PHP.
	 */
	protected function compilePhp(string|null $expression = null): string
	{
		if ($expression) {
			return "<?php {$expression}; ?>";
		}

		return '@php';
	}

	/**
	 * Compile the unset statements into valid PHP.
	 */
	protected function compileUnset(string|null $expression = null): string
	{
		return "<?php unset{$expression}; ?>";
	}
}
