<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesIncludes
{
	/**
	 * Compile the each statements into valid PHP.
	 */
	protected function compileEach(string $expression): string
	{
		return "<?php echo \$__env->renderEach{$expression}; ?>";
	}

	/**
	 * Compile the include statements into valid PHP.
	 */
	protected function compileInclude(string $expression): string
	{
		$expression = $this->stripParentheses($expression);

		return "<?php echo \$__env->make({$expression}, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-first statements into valid PHP.
	 */
	protected function compileIncludeFirst(string $expression): string
	{
		$expression = $this->stripParentheses($expression);

		return "<?php echo \$__env->first({$expression}, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-if statements into valid PHP.
	 */
	protected function compileIncludeIf(string $expression): string
	{
		$expression = $this->stripParentheses($expression);

		return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-unless statements into valid PHP.
	 */
	protected function compileIncludeUnless(string $expression): string
	{
		$expression = $this->stripParentheses($expression);

		return "<?php echo \$__env->renderUnless($expression, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
	}

	/**
	 * Compile the include-when statements into valid PHP.
	 */
	protected function compileIncludeWhen(string $expression): string
	{
		$expression = $this->stripParentheses($expression);

		return "<?php echo \$__env->renderWhen($expression, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
	}
}
