<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use ErrorException;
use Exception;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Errors\FatalError;
use MVPS\Lumis\Framework\Log\LogManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class HandleExceptions
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application|null
	 */
	protected static Application|null $app = null;

	/**
	 * Reserved memory so that errors can be displayed properly on memory exhaustion.
	 *
	 * @var string|null
	 */
	public static string|null $reservedMemory = null;

	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		static::$reservedMemory = str_repeat('x', 32768);

		static::$app = $app;

		error_reporting(-1);

		set_error_handler($this->forwardsTo('handleError'));

		set_exception_handler($this->forwardsTo('handleException'));

		register_shutdown_function($this->forwardsTo('handleShutdown'));

		if (! $app->environment('testing')) {
			ini_set('display_errors', 'Off');
		}
	}

	/**
	 * Create a new fatal error instance from an error array.
	 */
	protected function fatalErrorFromPhpError(array $error, int|null $traceOffset = null): FatalError
	{
		return new FatalError($error['message'], 0, $error, $traceOffset);
	}

	/**
	 * Flush the bootstrapper's global state.
	 */
	public static function flushState(): void
	{
		if (is_null(static::$app)) {
			return;
		}

		static::flushHandlersState();

		static::$app = null;

		static::$reservedMemory = null;
	}

	/**
	 * Flush the bootstrapper's global handlers state.
	 */
	public static function flushHandlersState(): void
	{
		while (true) {
			$previousHandler = set_exception_handler(static fn () => null);

			restore_exception_handler();

			if (is_null($previousHandler)) {
				break;
			}

			restore_exception_handler();
		}

		while (true) {
			$previousHandler = set_error_handler(static fn () => null);

			restore_error_handler();

			if (is_null($previousHandler)) {
				break;
			}

			restore_error_handler();
		}
	}

	/**
	 * Forward a method call to the given method if an application instance exists.
	 */
	protected function forwardsTo($method): callable
	{
		return fn (...$arguments) => static::$app
			? $this->{$method}(...$arguments)
			: false;
	}

	/**
	 * Get an instance of the exception handler.
	 */
	protected function getExceptionHandler(): ExceptionHandler
	{
		return static::$app->make(ExceptionHandler::class);
	}

	/**
	 * Reports a deprecation to the "deprecations" logger.
	 */
	public function handleDeprecationError(string $message, string $file, int $line, int $level = E_DEPRECATED): void
	{
		if ($this->shouldIgnoreDeprecationErrors()) {
			return;
		}

		try {
			$logger = static::$app->make(LogManager::class);
		} catch (Exception) {
			return;
		}

		$options = static::$app['config']->get('logging.deprecations') ?? [];

		if ($options['trace'] ?? false) {
			$logger->warning((string) new ErrorException($message, 0, $level, $file, $line));
		} else {
			$logger->warning(sprintf('%s in %s on line %s', $message, $file, $line));
		}
	}

	/**
	 * Report PHP deprecations, or convert PHP errors to ErrorException instances.
	 *
	 * @throws \ErrorException
	 */
	public function handleError(int $level, string $message, string $file = '', int $line = 0): void
	{
		if ($this->isDeprecation($level)) {
			$this->handleDeprecationError($message, $file, $line, $level);
		} elseif (error_reporting() & $level) {
			throw new ErrorException($message, 0, $level, $file, $line);
		}
	}

	/**
	 * Handles uncaught application exceptions.
	 *
	 * This method takes over situations where the exception bypasses the usual
	 * try/catch blocks within HTTP and Console kernels. It specifically
	 * targets fatal errors that require special handling.
	 */
	public function handleException(Throwable $e): void
	{
		static::$reservedMemory = null;

		try {
			$this->getExceptionHandler()->report($e);
		} catch (Exception) {
			$exceptionHandlerFailed = true;
		}

		if (static::$app->runningInConsole()) {
			$this->renderForConsole($e);

			if ($exceptionHandlerFailed ?? false) {
				exit(1);
			}
		} else {
			$this->renderHttpResponse($e);
		}
	}

	/**
	 * Handle the PHP shutdown event.
	 */
	public function handleShutdown(): void
	{
		static::$reservedMemory = null;

		$error = error_get_last();

		if (! is_null($error) && $this->isFatal($error['type'])) {
			$this->handleException($this->fatalErrorFromPhpError($error, 0));
		}
	}

	/**
	 * Determine if the error level is a deprecation.
	 */
	protected function isDeprecation(int $level): bool
	{
		return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
	}

	/**
	 * Determine if the error type is fatal.
	 */
	protected function isFatal(int $type): bool
	{
		return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
	}

	/**
	 * Render an exception to the console.
	 */
	protected function renderForConsole(Throwable $e): void
	{
		$this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
	}

	/**
	 * Render an exception as an HTTP response and send it.
	 */
	protected function renderHttpResponse(Throwable $e): void
	{
		$this->getExceptionHandler()->render(static::$app['request'], $e)
			->send();
	}

	/**
	 * Determine if deprecation errors should be ignored.
	 */
	protected function shouldIgnoreDeprecationErrors(): bool
	{
		return ! class_exists(LogManager::class) || ! static::$app->hasBeenBootstrapped();
	}
}
