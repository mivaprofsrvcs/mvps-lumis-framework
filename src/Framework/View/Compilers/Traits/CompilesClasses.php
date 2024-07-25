<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesClasses
{
	/**
	 * Compile the conditional class statement into valid PHP.
	 */
	protected function compileClass(string|null $expression = null): string
	{
		$expression = is_null($expression) ? '([])' : $expression;

		return "class=\"<?php echo \MVPS\Lumis\Framework\Support\Arr::toCssClasses{$expression}; ?>\"";
	}
}
