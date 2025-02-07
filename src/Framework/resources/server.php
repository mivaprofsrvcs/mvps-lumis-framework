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

$publicPath = getcwd();

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// This file emulates Apache's "mod_rewrite" functionality using PHP's built-in
// web server. It allows for convenient testing of a Lumis application without
// the need for a "real" web server.
if ($uri !== '/' && file_exists($publicPath . $uri)) {
	return false;
}

require_once $publicPath . '/index.php';
