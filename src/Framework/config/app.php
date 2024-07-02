<?php

use MVPS\Lumis\Framework\Support\ServiceProvider;

return [
	'asset_url' => env('ASSET_URL'),
	'debug' => (bool) env('APP_DEBUG', false),
	'env' => env('APP_ENV', 'production'),
	'name' => env('APP_NAME', 'Lumis'),
	'providers' => ServiceProvider::defaultProviders()->toArray(),
	'timezone' => env('APP_TIMEZONE', 'UTC'),
	'url' => env('APP_URL', 'http://localhost'),
];
