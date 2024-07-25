<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesStyles
{
	/**
	 * Compile the conditional style statement into valid PHP.
	 */
	protected function compileStyle(string|null $expression = null): string
	{
		$expression = is_null($expression) ? '([])' : $expression;

		return "style=\"<?php echo \MVPS\Lumis\Framework\Support\Arr::toCssStyles{$expression} ?>\"";
	}
}
