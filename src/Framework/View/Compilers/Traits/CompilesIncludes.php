<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesIncludes
{
	/**
	 * Compile the each statements into valid PHP.
	 */
	protected function compileEach(string|null $expression = null): string
	{
		return "<?php echo \$__env->renderEach{$expression}; ?>";
	}

	/**
	 * Compile the include statements into valid PHP.
	 */
	protected function compileInclude(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return "<?php echo \$__env->make({$expression}, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-first statements into valid PHP.
	 */
	protected function compileIncludeFirst(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return "<?php echo \$__env->first({$expression}, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-if statements into valid PHP.
	 */
	protected function compileIncludeIf(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
	}

	/**
	 * Compile the include-unless statements into valid PHP.
	 */
	protected function compileIncludeUnless(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return "<?php echo \$__env->renderUnless($expression, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
	}

	/**
	 * Compile the include-when statements into valid PHP.
	 */
	protected function compileIncludeWhen(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return "<?php echo \$__env->renderWhen($expression, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
	}
}
