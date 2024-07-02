<?php

$publicPath = getcwd();

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// This file emulates Apache's "mod_rewrite" functionality using PHP's built-in
// web server. It allows for convenient testing of a Lumis application without
// the need for a "real" web server.
if ($uri !== '/' && file_exists($publicPath . $uri)) {
	return false;
}

require_once $publicPath . '/index.php';
