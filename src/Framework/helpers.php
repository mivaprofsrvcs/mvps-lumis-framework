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

use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Http\Responsable;
use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Translation\Translator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Database\DatabaseManager;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\Http\RedirectResponse;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Log\LogManager;
use MVPS\Lumis\Framework\Routing\Redirector;
use MVPS\Lumis\Framework\Support\HtmlString;
use MVPS\Lumis\Framework\Support\Str;

if (! function_exists('abort')) {
	/**
	 * Terminates execution and generates an HTTP response.
	 *
	 * Creates an appropriate HTTP exception based on the provided data.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException
	 */
	function abort(Response|Responsable|int $code, string $message = '', array $headers = []): never
	{
		if ($code instanceof Response) {
			throw new HttpResponseException($code);
		} elseif ($code instanceof Responsable) {
			throw new HttpResponseException($code->toResponse(request()));
		}

		app()->abort($code, $message, $headers);
	}
}

if (! function_exists('abort_if')) {
	/**
	 * Throw an Http exception with the given data if the given condition
	 * is true.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException
	 */
	function abort_if(
		bool $boolean,
		Response|Responsable|int $code,
		string $message = '',
		array $headers = []
	): void {
		if ($boolean) {
			abort($code, $message, $headers);
		}
	}
}

if (! function_exists('abort_unless')) {
	/**
	 * Throw an Http exception with the given data unless the given condition
	 * is true.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundException
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException
	 */
	function abort_unless(
		bool $boolean,
		Response|Responsable|int $code,
		string $message = '',
		array $headers = []
	): void {
		if (! $boolean) {
			abort($code, $message, $headers);
		}
	}
}

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

if (! function_exists('asset')) {
	/**
	 * Generate an asset path for the application.
	 */
	function asset(string $path, bool|null $secure = null): string
	{
		return app('url')->asset($path, $secure);
	}
}

if (! function_exists('back')) {
	/**
	 * Create a new redirect response to the previous location.
	 */
	function back(int $status = 302, array $headers = [], mixed $fallback = false): RedirectResponse
	{
		return app('redirect')->back($status, $headers, $fallback);
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

if (! function_exists('cache')) {
	/**
	 * Retrieve or store a cache value.
	 *
	 * If an array is provided, the function assumes you want to store the value
	 * in the cache. Otherwise, it will retrieve the specified cache value.
	 *
	 * @throws \InvalidArgumentException If the provided argument is invalid.
	 */
	function cache(string|array|null $key = null, mixed $default = null): mixed
	{
		if (is_null($key)) {
			return app('cache');
		}

		if (is_string($key)) {
			return app('cache')->get($key, $default);
		}

		if (! is_array($key)) {
			throw new InvalidArgumentException(
				'An array of key-value pairs is required for cache storage.'
			);
		}

		return app('cache')->put(key($key), reset($key), ttl: $default);
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

// if (! function_exists('cookie')) {
// 	/**
// 	 * Create a new cookie instance.
// 	 */
// 	function cookie(
// 		string|null $name = null,
// 		string|null $value = null,
// 		int $minutes = 0,
// 		string|null $path = null,
// 		string|null $domain = null,
// 		bool|null $secure = null,
// 		bool $httpOnly = true,
// 		bool $raw = false,
// 		string|null $sameSite = null
// 	): CookieJar|Cookie {
// 		$cookie = app(CookieFactory::class);

// 		if (is_null($name)) {
// 			return $cookie;
// 		}

// 		return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
// 	}
// }

if (! function_exists('database_path')) {
	/**
	 * Get the database path.
	 */
	function database_path(string $path = ''): string
	{
		return app()->databasePath($path);
	}
}

if (! function_exists('db')) {
	/**
	 * Get the database manager instance.
	 */
	function db(): DatabaseManager
	{
		return app('db');
	}
}

if (! function_exists('event')) {
	/**
	 * Dispatches an event with the given arguments and
	 * invokes the corresponding listeners.
	 */
	function event(...$args): array|null
	{
		return app('events')->dispatch(...$args);
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

if (! function_exists('join_paths')) {
	/**
	 * Join the given paths together.
	 */
	function join_paths(string|null $basePath, string ...$paths): string
	{
		foreach ($paths as $index => $path) {
			if (empty($path) && $path !== '0') {
				unset($paths[$index]);
			} else {
				$paths[$index] = DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
			}
		}

		return $basePath . implode('', $paths);
	}
}

if (! function_exists('logger')) {
	/**
	 * Log a debug message to the logs.
	 *
	 * @return ($message is null ? \MVPS\Lumis\Framework\Log\LogManager : null)
	 */
	function logger(string|null $message = null, array $context = [])
	{
		if (is_null($message)) {
			return app('log');
		}

		return app('log')->debug($message, $context);
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

if (! function_exists('precognitive')) {
	/**
	 * Handle a precognition controller hook.
	 */
	function precognitive(null|callable $callable = null): mixed
	{
		$callable ??= function () {
			//
		};

		$payload = $callable(function ($default, $precognition = null) {
			$response = request()->isPrecognitive()
				? ($precognition ?? $default)
				: $default;

			abort(app('router')->toResponse(request(), value($response)));
		});

		if (request()->isPrecognitive()) {
			abort(204, headers: ['Precognition-Success' => 'true']);
		}

		return $payload;
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

if (! function_exists('redirect')) {
	/**
	 * Creates a redirect response or retrieves the redirector instance.
	 *
	 * If a target URL is provided, a RedirectResponse instance is returned.
	 * Otherwise, the redirector instance is returned for building redirects.
	 */
	function redirect(
		string|null $to = null,
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): Redirector|RedirectResponse {
		if (is_null($to)) {
			return app('redirect');
		}

		return app('redirect')->to($to, $status, $headers, $secure);
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

		$value = app('request')->__get($key);

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
	function response(mixed $content = '', $status = 200, array $headers = []): ResponseFactory|Response
	{
		$factory = app(ResponseFactory::class);

		if (func_num_args() === 0) {
			return $factory;
		}

		return $factory->make($content ?? '', $status, $headers);

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

if (! function_exists('to_route')) {
	/**
	 * Create a new redirect response to a named route.
	 */
	function to_route(string $route, mixed $parameters = [], int $status = 302, array $headers = []): RedirectResponse
	{
		return redirect()->route($route, $parameters, $status, $headers);
	}
}

if (! function_exists('trans')) {
	/**
	 * Translate the given message.
	 */
	function trans(string|null $key = null, array $replace = []): Translator|array|string
	{
		if (is_null($key)) {
			return app('translator');
		}

		return app('translator')->get($key, $replace);
	}
}

if (! function_exists('trans_choice')) {
	/**
	 * Translates the given message based on a count.
	 */
	function trans_choice(string $key, \Countable|int|float|array $number, array $replace = []): string
	{
		return app('translator')->choice($key, $number, $replace);
	}
}

if (! function_exists('__')) {
	/**
	 * Translate the given message.
	 */
	function __(string|null $key = null, array $replace = []): string|array|null
	{
		if (is_null($key)) {
			return $key;
		}

		return trans($key, $replace);
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

if (! function_exists('validator')) {
	/**
	 * Create a new validator instance.
	 */
	function validator(
		array|null $data = null,
		array $rules = [],
		array $messages = [],
		array $attributes = []
	): ValidationFactory|ValidatorContract {
		$factory = app(ValidationFactory::class);

		if (func_num_args() === 0) {
			return $factory;
		}

		return $factory->make($data ?? [], $rules, $messages, $attributes);
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
