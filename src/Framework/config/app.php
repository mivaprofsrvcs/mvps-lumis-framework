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
