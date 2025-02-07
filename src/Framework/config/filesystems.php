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
	| Default Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Configure the default filesystem disk used for file storage operations.
	| Available disks include "local" and various cloud storage options.
	|
	*/
	'default' => env('FILESYSTEM_DISK', 'local'),

	/*
	|--------------------------------------------------------------------------
	| Filesystem Disks
	|--------------------------------------------------------------------------
	|
	| Configure multiple filesystem disks for various storage needs.
	| Define disks with their driver, root path, and optional configurations.
	| Supported drivers include "local", "ftp", and "sftp".
	|
	*/
	'disks' => [
		'local' => [
			'driver' => 'local',
			'root' => storage_path('app'),
			'throw' => false,
		],
		'public' => [
			'driver' => 'local',
			'root' => storage_path('app/public'),
			'url' => env('APP_URL') . '/storage',
			'visibility' => 'public',
			'throw' => false,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Symbolic Links
	|--------------------------------------------------------------------------
	|
	| Define symbolic links to be created by the `storage:link` Lumis command.
	| Map link paths (array keys) to their target directories (array values).
	|
	*/
	'links' => [
		public_path('storage') => storage_path('app/public'),
	],
];
