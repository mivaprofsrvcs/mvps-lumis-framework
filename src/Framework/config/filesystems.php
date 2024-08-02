<?php

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
