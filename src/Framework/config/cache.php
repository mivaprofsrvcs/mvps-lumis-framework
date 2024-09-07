<?php

use MVPS\Lumis\Framework\Support\Str;

return [
	/*
	|--------------------------------------------------------------------------
	| Default Cache Store
	|--------------------------------------------------------------------------
	|
	| This setting specifies the default cache store that Lumis will use. If no
	| specific store is defined during a cache operation, Lumis will rely on
	| this primary connection to manage the cached data within the application.
	|
	*/
	'default' => env('CACHE_STORE', 'database'),

	/*
	|--------------------------------------------------------------------------
	| Cache Stores
	|--------------------------------------------------------------------------
	|
	| In this section, you can configure all of the cache "stores" that can
	| be utilized. Each store can use a different driver, and you can even
	| set up multiple stores under the same driver to better organize your
	| cached data. This gives you flexibility in managing different types of
	| cached items across various storage mechanisms.
	|
	| Supported drivers: "array", "database", "file", "null"
	|
	*/
	'stores' => [
		'array' => [
			'driver' => 'array',
			'serialize' => false,
		],
		'database' => [
			'driver' => 'database',
			'connection' => env('DB_CACHE_CONNECTION'),
			'table' => env('DB_CACHE_TABLE', 'cache'),
			'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
			'lock_table' => env('DB_CACHE_LOCK_TABLE'),
		],
		'file' => [
			'driver' => 'file',
			'path' => storage_path('framework/cache/data'),
			'lock_path' => storage_path('framework/cache/data'),
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Cache Key Prefix
	|--------------------------------------------------------------------------
	|
	| When using cache stores like APC or database, multiple applications
	| might share the same cache storage. To prevent conflicts between cache
	| keys from different apps, Lumis allows you to define a prefix. This
	| prefix will be added to every cache key, ensuring that your cache
	| entries remain  unique.
	|
	*/
	'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'lumis'), '_') . '_cache_'),
];
