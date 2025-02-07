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

return [

	/*
	|--------------------------------------------------------------------------
	| View Storage Paths
	|--------------------------------------------------------------------------
	|
	| Templating systems typically load templates from the disk. Here, you can
	| specify an array of paths that should be checked for your views. The
	| default Lumis view path is already registered for you.
	|
	*/
	'paths' => [
		resource_path('views'),
	],

	/*
	|--------------------------------------------------------------------------
	| Compiled View Path
	|--------------------------------------------------------------------------
	|
	| This option specifies where all the compiled Blade templates will be
	| stored for your application. Typically, this is within the storage
	| directory. However, you are free to change this value if needed.
	|
	*/
	'compiled' => env(
		'VIEW_COMPILED_PATH',
		realpath(storage_path('framework/views'))
	),

];
