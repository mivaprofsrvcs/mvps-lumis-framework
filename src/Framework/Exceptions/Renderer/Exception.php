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

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use Closure;
use Composer\Autoload\ClassLoader;
use MVPS\Lumis\Framework\Bootstrap\HandleExceptions;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Http\Request;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Exception
{
	/**
	 * The application's base path.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * The "flattened" exception instance.
	 *
	 * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
	 */
	protected FlattenException $exception;

	/**
	 * The exception listener instance.
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Renderer\Listener
	 */
	protected Listener $listener;

	/**
	 * The current request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request $request;

	/**
	 * Creates a new exception renderer instance.
	 */
	public function __construct(FlattenException $exception, Request $request, Listener $listener, string $basePath)
	{
		$this->exception = $exception;
		$this->request = $request;
		$this->listener = $listener;
		$this->basePath = $basePath;
	}

	/**
	 * Get the application's SQL queries.
	 */
	public function applicationQueries(): array
	{
		return array_map(
			function (array $query) {
				$sql = $query['sql'];

				foreach ($query['bindings'] as $binding) {
					$sql = match (gettype($binding)) {
						'integer', 'double' => preg_replace('/\?/', $binding, $sql, 1),
						'NULL' => preg_replace('/\?/', 'NULL', $sql, 1),
						default => preg_replace('/\?/', "'$binding'", $sql, 1),
					};
				}

				return [
					'connectionName' => $query['connectionName'],
					'time' => $query['time'],
					'sql' => $sql,
				];
			},
			$this->listener->queries()
		);
	}

	/**
	 * Get the application's route context.
	 */
	public function applicationRouteContext(): array
	{
		$route = $this->request()->route();

		return $route ? array_filter([
			'controller' => $route->getActionName(),
			'route name' => $route->getName() ?: null,
			'middleware' => implode(', ', array_map(
				fn ($middleware) => $middleware instanceof Closure ? 'Closure' : $middleware,
				$route->gatherMiddleware()
			)),
		]) : [];
	}

	/**
	 * Get the application's route parameters context.
	 */
	public function applicationRouteParametersContext(): string|null
	{
		$parameters = $this->request()->route()?->parameters();

		return $parameters ? json_encode(
			array_map(fn ($value) => $value, (array) $parameters),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
		) : null;
	}

	/**
	 * Get the exception class name.
	 */
	public function class(): string
	{
		return $this->exception->getClass();
	}

	/**
	 * Get the first "non-vendor" frame index.
	 */
	public function defaultFrame(): int
	{
		$key = array_search(false, array_map(
			fn (Frame $frame) => $frame->isFromVendor(),
			$this->frames()->all()
		));

		return $key === false ? 0 : $key;
	}

	/**
	 * Get the exception's frames.
	 */
	public function frames(): Collection
	{
		$classMap = once(fn () => array_map(function ($path) {
			return (string) realpath($path);
		}, array_values(ClassLoader::getRegisteredLoaders())[0]->getClassMap()));

		$trace = array_values(array_filter(
			$this->exception->getTrace(),
			fn ($trace) => isset($trace['file']),
		));

		if (($trace[1]['class'] ?? '') === HandleExceptions::class) {
			array_shift($trace);
			array_shift($trace);
		}

		return collection(array_map(
			fn (array $trace) => new Frame($this->exception, $classMap, $trace, $this->basePath),
			$trace
		));
	}

	/**
	 * Get the exception message.
	 */
	public function message(): string
	{
		return $this->exception->getMessage();
	}

	/**
	 * Get the exception's request instance.
	 */
	public function request(): Request
	{
		return $this->request;
	}

	/**
	 * Get the request's body parameters.
	 */
	public function requestBody(): string|null
	{
		$payload = $this->request()->all();

		if (empty($payload)) {
			return null;
		}

		$json = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		return str_replace('\\', '', $json);
	}

	/**
	 * Get the request's headers.
	 */
	public function requestHeaders(): array
	{
		return array_map(
			fn (array $header) => implode(', ', $header),
			$this->request()->getHeaders()
		);
	}

	/**
	 * Get the exception title.
	 */
	public function title(): string
	{
		return $this->exception->getStatusText();
	}
}
