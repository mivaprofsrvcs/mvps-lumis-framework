<?php

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
