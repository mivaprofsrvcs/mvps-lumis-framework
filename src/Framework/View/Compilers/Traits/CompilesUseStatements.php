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

trait CompilesUseStatements
{
	/**
	 * Compile the use statements into valid PHP.
	 */
	protected function compileUse(string|null $expression = null): string
	{
		$segments = explode(',', preg_replace("/[\(\)]/", '', $expression ?? ''));

		$use = ltrim(trim($segments[0], " '\""), '\\');
		$as = isset($segments[1]) ? ' as ' . trim($segments[1], " '\"") : '';

		return "<?php use \\{$use}{$as}; ?>";
	}
}
