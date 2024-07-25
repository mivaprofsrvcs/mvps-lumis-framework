<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesInjections
{
	/**
	 * Compile the inject statements into valid PHP.
	 */
	protected function compileInject(string|null $expression = null): string
	{
		$segments = explode(',', preg_replace("/[\(\)]/", '', $expression ?? ''));

		$variable = trim($segments[0], " '\"");

		$service = trim($segments[1]);

		return "<?php \${$variable} = app({$service}); ?>";
	}
}
