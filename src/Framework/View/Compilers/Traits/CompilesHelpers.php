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
