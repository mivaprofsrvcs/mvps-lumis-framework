<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesClasses
{
	/**
	 * Compile the conditional class statement into valid PHP.
	 */
	protected function compileClass(string $expression): string
	{
		$expression = is_null($expression) ? '([])' : $expression;

		return "class=\"<?php echo \MVPS\Lumis\Framework\Collections\Arr::toCssClasses{$expression}; ?>\"";
	}
}
