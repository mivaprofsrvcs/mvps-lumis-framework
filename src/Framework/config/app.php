<?php

use MVPS\Lumis\Framework\Providers\ServiceProvider;

return [
	/*
	|--------------------------------------------------------------------------
	| Application Asset URL
	|--------------------------------------------------------------------------
	|
	| URL used to access static assets (images, CSS, JS)
	|
	*/
	'asset_url' => env('ASSET_URL'),

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| Enable detailed error messages with stack traces on every error. If
	| disabled, a simple generic error page is shown.
	|
	*/
	'debug' => (bool) env('APP_DEBUG', false),

	/*
	|--------------------------------------------------------------------------
	| Application Environment
	|--------------------------------------------------------------------------
	|
	| Specifies the environment in which the application is running. This can
	| influence how various services are configured.
	|
	*/
	'env' => env('APP_ENV', 'production'),

	/*
	|--------------------------------------------------------------------------
	| Application Name
	|--------------------------------------------------------------------------
	|
	| The name of the application. This is used in notifications and UI
	| elements.
	|
	*/
	'name' => env('APP_NAME', 'Lumis'),

	/*
	|--------------------------------------------------------------------------
	| Application Service Providers
	|--------------------------------------------------------------------------
	|
	| List of application service providers to be registered.
	|
	*/
	'providers' => ServiceProvider::defaultProviders()->toArray(),

	/*
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Specifies the default timezone for the application. This affects the PHP
	| date and date-time functions. The default is "UTC".
	|
	*/
	'timezone' => env('APP_TIMEZONE', 'UTC'),

	/*
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	|
	| The base URL of the application. This is used by the console to generate
	| URLs correctly when using the Lumis command line tool.
	|
	*/
	'url' => env('APP_URL', 'http://localhost'),
];
