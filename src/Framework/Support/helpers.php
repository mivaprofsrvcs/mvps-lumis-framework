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

if (! function_exists('dp')) {
	/**
	 * Print variable with optional label.
	 */
	function dp(mixed $data, string $label = ''): void
	{
		$isCli = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
		$eol = $isCli ? PHP_EOL : '<br>';
		$printVariable = is_array($data) || is_object($data);

		if ($isCli) {
			echo $label ? $eol . $label . ':' : '',
				$eol,
				$printVariable ? print_r($data) : $data,
				$eol;
		} else {
			echo $label ? $eol . $label . ':' : '',
				$eol . '<pre>',
				$printVariable ? print_r($data) : $data,
				'<pre>' . $eol;
		}
	}
}

if (! function_exists('dpx')) {
	/**
	 * Print string with all applicable characters converted to HTML entities.
	 */
	function dpx(string $data, string $label = ''): void
	{
		$isCli = in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
		$eol = $isCli ? PHP_EOL : '<br>';
		$dataOutput = htmlentities($data);

		if ($isCli) {
			echo $label ? $eol . $label . ':' : '',
				$eol,
				$dataOutput,
				$eol;
		} else {
			echo $label ? $eol . $label . ':' : '',
				$eol . '<pre style="white-space:pre-wrap">',
				$dataOutput,
				'</pre>' . $eol;
		}
	}
}
