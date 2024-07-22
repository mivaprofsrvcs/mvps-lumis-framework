<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Deprecation Logging Configuration
	|--------------------------------------------------------------------------
	|
	| This section controls logging behavior for deprecated functionalities.
	| Enabling deprecation logging allows you to identify and address usage
	| of deprecated PHP features and library functions within your application.
	| This proactive approach helps prepare your application for upcoming major
	| versions of dependencies that may remove these functionalities.
	|
	| Options:
	|
	| * `trace`: (boolean) Enables logging of deprecation stack traces.
	|           Setting this to `true` provides more detailed information about
	|           where the deprecation originates within your codebase.
	|
	*/
	'deprecations' => [
		'trace' => env('LOG_DEPRECATIONS_TRACE', false),
	],

	/*
	|--------------------------------------------------------------------------
	| Log File Path
	|--------------------------------------------------------------------------
	|
	| This option defines the path to the log file where application logs
	| will be written. By default, it points to the `lumis.log` file within
	| the Lumis application's `storage/logs` directory.
	|
	*/
	'path' => storage_path('logs/lumis.log'),

	/*
	|--------------------------------------------------------------------------
	| Log Level
	|--------------------------------------------------------------------------
	|
	| This option determines the minimum severity level of messages that
	| will be logged. Messages with a level lower than the specified level
	| will not be written to the log file. Available levels include:
	|
	| * `debug`
	| * `info`
	| * `notice`
	| * `warning`
	| * `error`
	| * `alert`
	| * `emergency`
	|
	| The default level is set to `debug` to capture all messages during
	| development. You should adjust this level based on your application's
	| logging requirements in production environments.
	|
	*/
	'level' => env('LOG_LEVEL', 'debug'),

	/*
	|--------------------------------------------------------------------------
	| Replace Placeholders in Log Messages
	|--------------------------------------------------------------------------
	|
	| This option controls whether environment variables and other
	| placeholders within log messages should be replaced with their
	| actual values. Setting  this to `true` allows for more readable
	| and informative log messages.
	|
	*/
	'replace_placeholders' => true,
];
