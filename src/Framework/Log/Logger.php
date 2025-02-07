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

namespace MVPS\Lumis\Framework\Log;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use MVPS\Lumis\Framework\Log\Events\MessageLogged;
use MVPS\Lumis\Framework\Support\Stringable;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Logger implements LoggerInterface
{
	use Conditionable;

	/**
	 * The underlying logger implementation.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher|null
	 */
	protected Dispatcher|null $dispatcher;

	/**
	 * Any context to be added to logs.
	 *
	 * @var array
	 */
	protected array $context = [];

	/**
	 * Create a new log writer instance.
	 */
	public function __construct(LoggerInterface $logger, Dispatcher|null $dispatcher = null)
	{
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Log an alert message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function alert($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a critical message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function critical($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a debug message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function debug($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an emergency message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function emergency($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an error message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function error($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an informational message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function info($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a message to the logs.
	 *
	 * @param  string  $level
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void
	{
		$this->writeLog($level, $message, $context);
	}

	/**
	 * Log a notice to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function notice($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a warning message to the logs.
	 *
	 * @param  \MVPS\Lumis\Framework\Contracts\Support\Arrayable|\MVPS\Lumis\Framework\Contracts\Support\Jsonable|
	 *         \MVPS\Lumis\Framework\Support\Stringable\Stringable|array|string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function warning($message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Fires a log event.
	 */
	protected function fireLogEvent(string $level, string $message, array $context = []): void
	{
		$this->dispatcher?->dispatch(new MessageLogged($level, $message, $context));
	}

	/**
	 * Format the parameters for the logger.
	 */
	protected function formatMessage(Arrayable|Jsonable|Stringable|array|string $message): string
	{
		if (is_array($message)) {
			return var_export($message, true);
		} elseif ($message instanceof Jsonable) {
			return $message->toJson();
		} elseif ($message instanceof Arrayable) {
			return var_export($message->toArray(), true);
		}

		return (string) $message;
	}

	/**
	 * Get the event dispatcher instance.
	 */
	public function getEventDispatcher(): Dispatcher
	{
		return $this->dispatcher;
	}

	/**
	 * Get the underlying logger implementation.
	 */
	public function getLogger(): LoggerInterface
	{
		return $this->logger;
	}

	/**
	 * Register a new callback handler for when a log event is triggered.
	 *
	 * @throws \RuntimeException
	 */
	public function listen(Closure $callback): void
	{
		if (! isset($this->dispatcher)) {
			throw new RuntimeException('Events dispatcher has not been set.');
		}

		$this->dispatcher->listen(MessageLogged::class, $callback);
	}

	/**
	 * Set the event dispatcher instance.
	 */
	public function setEventDispatcher(Dispatcher $dispatcher): void
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Add context to all future logs.
	 */
	public function withContext(array $context = []): static
	{
		$this->context = array_merge($this->context, $context);

		return $this;
	}

	/**
	 * Flush the existing context array.
	 */
	public function withoutContext(): static
	{
		$this->context = [];

		return $this;
	}

	/**
	 * Dynamically pass log calls into the writer.
	 */
	public function write(string $level, Arrayable|Jsonable|Stringable|array|string $message, array $context = []): void
	{
		$this->writeLog($level, $message, $context);
	}

	/**
	 * Write a message to the log.
	 */
	protected function writeLog(
		string $level,
		Arrayable|Jsonable|Stringable|array|string $message,
		array $context
	): void {
		$message = $this->formatMessage($message);
		$context = array_merge($this->context, $context);

		$this->logger->{$level}($message, $context);

		$this->fireLogEvent($level, $message, $context);
	}

	/**
	 * Dynamically proxy method calls to the underlying logger.
	 */
	public function __call($method, $parameters)
	{
		return $this->logger->{$method}(...$parameters);
	}
}
