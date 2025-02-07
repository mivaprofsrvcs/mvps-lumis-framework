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

trait CompilesFragments
{
	/**
	 * The last compiled fragment.
	 *
	 * @var string
	 */
	protected string $lastFragment;

	/**
	 * Compile the end-fragment statements into valid PHP.
	 */
	protected function compileEndfragment(): string
	{
		return '<?php echo $__env->stopFragment(); ?>';
	}

	/**
	 * Compile the fragment statements into valid PHP.
	 */
	protected function compileFragment(string|null $expression = null): string
	{
		$this->lastFragment = trim($expression ?? '', "()'\" ");

		return "<?php \$__env->startFragment{$expression}; ?>";
	}
}
