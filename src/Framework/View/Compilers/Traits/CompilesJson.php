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

trait CompilesJson
{
	/**
	 * The default JSON encoding options.
	 *
	 * @var int
	 */
	private int $encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

	/**
	 * Compile the JSON statement into valid PHP.
	 */
	protected function compileJson(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''));

		$options = isset($parts[1]) ? trim($parts[1]) : $this->encodingOptions;

		$depth = isset($parts[2]) ? trim($parts[2]) : 512;

		return "<?php echo json_encode($parts[0], $options, $depth) ?>";
	}
}
