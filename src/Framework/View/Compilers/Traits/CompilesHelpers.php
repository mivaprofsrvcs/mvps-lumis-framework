<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesHelpers
{
	/**
	 * Compile the "dd" statements into valid PHP.
	 */
	protected function compileDd(string|null $arguments = null): string
	{
		return "<?php dd{$arguments}; ?>";
	}

	/**
	 * Compile the "dump" statements into valid PHP.
	 */
	protected function compileDump(string|null $arguments = null): string
	{
		return "<?php dump{$arguments}; ?>";
	}

	/**
	 * Compile the method statements into valid PHP.
	 */
	protected function compileMethod(string|null $method = null): string
	{
		return "<?php echo method_field{$method}; ?>";
	}
}
