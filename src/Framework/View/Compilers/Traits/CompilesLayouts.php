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

trait CompilesLayouts
{
	/**
	 * The name of the last section that was started.
	 *
	 * @var string
	 */
	protected string $lastSection;

	/**
	 * Compile the append statements into valid PHP.
	 */
	protected function compileAppend(): string
	{
		return '<?php $__env->appendSection(); ?>';
	}

	/**
	 * Compile the end-section statements into valid PHP.
	 */
	protected function compileEndsection(): string
	{
		return '<?php $__env->stopSection(); ?>';
	}

	/**
	 * Compile the extends statements into valid PHP.
	 */
	protected function compileExtends(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		$echo = "<?php echo \$__env->make({$expression}, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";

		$this->footer[] = $echo;

		return '';
	}

	/**
	 * Compile the extends-first statements into valid PHP.
	 */
	protected function compileExtendsFirst(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		$echo = "<?php echo \$__env->first({$expression}, \MVPS\Lumis\Framework\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";

		$this->footer[] = $echo;

		return '';
	}

	/**
	 * Compile the overwrite statements into valid PHP.
	 */
	protected function compileOverwrite(): string
	{
		return '<?php $__env->stopSection(true); ?>';
	}

	/**
	 * Replace the @parent directive to a placeholder.
	 */
	protected function compileParent(): string
	{
		$escapedLastSection = strtr($this->lastSection, ['\\' => '\\\\', "'" => "\\'"]);

		return "<?php echo \MVPS\Lumis\Framework\View\Factory::parentPlaceholder('{$escapedLastSection}'); ?>";
	}

	/**
	 * Compile the section statements into valid PHP.
	 */
	protected function compileSection(string|null $expression = null): string
	{
		$this->lastSection = trim($expression ?? '', "()'\" ");

		return "<?php \$__env->startSection{$expression}; ?>";
	}

	/**
	 * Compile the show statements into valid PHP.
	 */
	protected function compileShow(): string
	{
		return '<?php echo $__env->yieldSection(); ?>';
	}

	/**
	 * Compile the stop statements into valid PHP.
	 */
	protected function compileStop(): string
	{
		return '<?php $__env->stopSection(); ?>';
	}

	/**
	 * Compile the yield statements into valid PHP.
	 */
	protected function compileYield(string|null $expression = null): string
	{
		return "<?php echo \$__env->yieldContent{$expression}; ?>";
	}
}
