<?php

use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\ResponseFactory;
use MVPS\Lumis\Framework\Support\HtmlString;
use MVPS\Lumis\Framework\Support\Str;

if (! function_exists('action')) {
	/**
	 * Generate the URL to a controller action.
	 */
	function action(string|array $name, mixed $parameters = [], bool $absolute = true): string
	{
		return app('url')->action($name, $parameters, $absolute);
	}
}

if (! function_exists('app')) {
	/**
	 * Get the available container instance.
	 */
	function app(string|null $abstract = null, array $parameters = []): mixed
	{
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($abstract, $parameters);
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

if (! function_exists('database_path')) {
	/**
	 * Get the database path.
	 */
	function database_path(string $path = ''): string
	{
		return app()->databasePath($path);
	}
}

if (! function_exists('fake') && class_exists(FakerFactory::class)) {
	/**
	 * Get a faker instance.
	 */
	function fake(string|null $locale = null): FakerGenerator
	{
		if (app()->bound('config')) {
			$locale ??= app('config')->get('app.faker_locale');
		}

		$locale ??= 'en_US';

		$abstract = FakerGenerator::class . ':' . $locale;

		if (! app()->bound($abstract)) {
			app()->singleton($abstract, fn () => FakerFactory::create($locale));
		}

		return app()->make($abstract);
	}
}

if (! function_exists('method_field')) {
	/**
	 * Generate a hidden form field to spoof the HTTP verb used by the form.
	 *
	 * This is typically used when the form method is set to 'POST' but needs
	 * to simulate a different HTTP verb such as 'PUT', 'PATCH', or 'DELETE'.
	 */
	function method_field(string $method): HtmlString
	{
		return new HtmlString('<input type="hidden" name="_method" value="' . $method . '">');
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

if (! function_exists('report')) {
	/**
	 * Report an exception.
	 */
	function report(Throwable|string $exception): void
	{
		if (is_string($exception)) {
			$exception = new Exception($exception);
		}

		app(ExceptionHandler::class)->report($exception);
	}
}

if (! function_exists('report_if')) {
	/**
	 * Report an exception if the given condition is true.
	 */
	function report_if(bool $boolean, Throwable|string $exception): void
	{
		if ($boolean) {
			report($exception);
		}
	}
}

if (! function_exists('report_unless')) {
	/**
	 * Report an exception unless the given condition is true.
	 */
	function report_unless(bool $boolean, Throwable|string $exception): void
	{
		if (! $boolean) {
			report($exception);
		}
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

if (! function_exists('rescue')) {
	/**
	 * Catch a potential exception and return a default value.
	 */
	function rescue(callable $callback, mixed $rescue = null, bool|callable $report = true): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			if (value($report, $e)) {
				report($e);
			}

			return value($rescue, $e);
		}
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
		return app(ResponseFactory::class)->make($content, $status, $headers);
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

if (! function_exists('stringable')) {
	/**
	 * Get a new stringable object from the given string.
	 */
	function stringable(string|null $string = null): mixed
	{
		if (func_num_args() === 0) {
			return new class
			{
				public function __call($method, $parameters)
				{
					return Str::$method(...$parameters);
				}

				public function __toString()
				{
					return '';
				}
			};
		}

		return Str::of($string);
	}
}

if (! function_exists('task_path')) {
	/**
	 * Get the path to the tasks directory.
	 */
	function task_path(string $path = ''): string
	{
		return app()->taskPath($path);
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

if (! function_exists('view')) {
	/**
	 * Get the evaluated view contents for the given view.
	 */
	function view(string|null $view = null, Arrayable|array $data = [], array $mergeData = []): View|ViewFactory
	{
		$factory = app(ViewFactory::class);

		if (func_num_args() === 0) {
			return $factory;
		}

		return $factory->make($view, $data, $mergeData);
	}
}
