<?php

use MVPS\Lumis\Framework\Container\Container;

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

if (! function_exists('public_path')) {
	/**
	 * Get the path to the public directory.
	 */
	function public_path(string $path = ''): string
	{
		return app()->publicPath($path);
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

if (! function_exists('resources_path')) {
	/**
	 * Get the path to the resources directory.
	 */
	function resources_path(string $path = ''): string
	{
		return app()->resourcePath($path);
	}
}

if (! function_exists('storage_path')) {
	/**
	 * Get the path to the storage directory.
	 *
	 * @param  string  $path
	 * @return string
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
