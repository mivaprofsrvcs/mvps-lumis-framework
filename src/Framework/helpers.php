<?php

use Carbon\Carbon;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\ResponseFactory;

if (! function_exists('app')) {
	/**
	 * Get the available container instance.
	 */
	function app(string|null $abstract = null, array $parameters = []): mixed
	{
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()
			->make($abstract, $parameters);
	}
}

if (! function_exists('app_path')) {
	/**
	 * Get the path to the application directory.
	 */
	function app_path(string $path = ''): string
	{
		return app()->path($path);
	}
}

if (! function_exists('base_path')) {
	/**
	 * Get the path to the base of the install.
	 */
	function base_path(string $path = ''): string
	{
		return app()->basePath($path);
	}
}

if (! function_exists('config')) {
	/**
	 * Get / set the specified configuration value.
	 * Will return all configuration values if no key is passed in.
	 * Will set an array of values if an array is passed as the key.
	 */
	function config(array|string|null $key = null, mixed $default = null): mixed
	{
		if (is_null($key)) {
			return app('config');
		}

		if (is_array($key)) {
			return app('config')->set($key);
		}

		return app('config')->get($key, $default);
	}
}

if (! function_exists('config_path')) {
	/**
	 * Get the path to the configuration directory.
	 */
	function config_path(string $path = ''): string
	{
		return app()->configPath($path);
	}
}

if (! function_exists('now')) {
	/**
	 * Create a new carbon instance for the current time.
	 */
	function now(DateTimeZone|string|null $timezone = null): Carbon
	{
		return Carbon::now($timezone);
	}
}

if (! function_exists('public_path')) {
	/**
	 * Get the path to the public directory.
	 */
	function public_path(string $path = ''): string
	{
		return app()->publicPath($path);
	}
}

if (! function_exists('request')) {
	/**
	 * Get an instance of the current request or an input item from the request.
	 */
	function request(array|string|null $key = null, mixed $default = null): Request|string|array|null
	{
		$request = app('request');

		if (is_null($key)) {
			return $request;
		}

		if (is_array($key)) {
			return $request->only($key);
		}

		$value = $request->input($key);

		return is_null($value) ? value($default) : $value;
	}
}

if (! function_exists('resolve')) {
	/**
	 * Resolve a service from the container.
	 */
	function resolve(string $name, array $parameters = []): mixed
	{
		return app($name, $parameters);
	}
}

if (! function_exists('resource_path')) {
	/**
	 * Get the path to the resources directory.
	 */
	function resource_path(string $path = ''): string
	{
		return app()->resourcePath($path);
	}
}

if (! function_exists('response')) {
	/**
	 * Create and return a new response from the application.
	 */
	function response(mixed $content = '', $status = 200, array $headers = []): Response
	{
		return (new ResponseFactory)->make($content, $status, $headers);
	}
}

if (! function_exists('route')) {
	/**
	 * Generate the URL to a named route.
	 */
	function route(string $name, mixed $parameters = [], bool $absolute = true): string
	{
		return app('url')->route($name, $parameters, $absolute);
	}
}

if (! function_exists('storage_path')) {
	/**
	 * Get the path to the storage directory.
	 */
	function storage_path(string $path = ''): string
	{
		return app()->storagePath($path);
	}
}

if (! function_exists('tasks_path')) {
	/**
	 * Get the path to the tasks directory.
	 */
	function tasks_path(string $path = ''): string
	{
		return app()->tasksPath($path);
	}
}

if (! function_exists('url')) {
	/**
	 * Generate a url for the application.
	 */
	function url(string|null $path = null, mixed $parameters = [], bool|null $secure = null): UrlGenerator|string
	{
		if (is_null($path)) {
			return app(UrlGenerator::class);
		}

		return app(UrlGenerator::class)->to($path, $parameters, $secure);
	}
}
