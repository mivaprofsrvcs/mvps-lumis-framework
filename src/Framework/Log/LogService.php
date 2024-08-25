<?php

namespace MVPS\Lumis\Framework\Log;

use BackedEnum;
use Closure;
use DateTimeInterface;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use MVPS\Lumis\Framework\Log\Events\MessageLogged;
use MVPS\Lumis\Framework\Support\Str;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;
use UnexpectedValueException;
use UnitEnum;

class LogService implements LoggerInterface
{
	/**
	 * The Lumis application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	 /**
	 * Any context to be added to logs.
	 *
	 * @var array
	 */
	protected array $context = [];

	/**
	 * The default date format used for timestamps within log messages.
	 *
	 * This property defines the format string used to format timestamps within
	 * log entries. The format string should adhere to the PHP date function
	 * format specifiers.
	 *
	 * @link https://www.php.net/manual/en/function.date.php
	 *
	 * @var string
	 */
	protected string $dateFormat = '[Y-m-d H:i:s]';

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher|null
	 */
	protected Dispatcher|null $dispatcher;

	/**
	 * The log file.
	 *
	 * @var string
	 */
	protected string $logFile;

	/**
	 * The file logger instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected Filesystem $logger;

	/**
	 * Determines if log message placeholders are replaced with actual values.
	 *
	 * @var bool
	 */
	protected bool $replacePlaceholders = true;

	/**
	 * Create a new log writer instance.
	 */
	public function __construct(
		Application|null $app = null,
		Filesystem|null $logger = null,
		Dispatcher|null $dispatcher = null,
		string|null $logFile = null,
		bool $replacePlaceholders = true
	) {
		$this->app = $app ?: app();
		$this->logger = $logger ?: $this->app['files'] ?? new Filesystem;
		$this->dispatcher = $dispatcher;
		$this->logFile = $logFile ?: $this->app->storagePath('logs/lumis.log');
		$this->replacePlaceholders = $replacePlaceholders;

		$this->validateLogFile();
	}

	/**
	 * Log an alert message to the log.
	 */
	public function alert(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Log a critical message to the log.
	 */
	public function critical(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Log a debug message to the log.
	 */
	public function debug(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * Log an emergency message to the log.
	 */
	public function emergency(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Log an error message to the log.
	 */
	public function error(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::ERROR, $message, $context);
	}

	/**
	 * Fires a log event.
	 */
	protected function fireLogEvent(string $level, string $message, array $context = []): void
	{
		$this->dispatcher?->dispatch(new MessageLogged($level, $message, $context));
	}

	/**
	 * Formats a log message with timestamp (optional), level, and message content.
	 *
	 * This method constructs a formatted log message string by combining:
	 *  - A timestamp prepended to the message, based on the `$dateFormat` property.
	 *  - The log message level converted to uppercase (e.g., "INFO", "WARNING").
	 *  - The actual message content with a trailing newline character.
	 */
	protected function formatLogMessage(string $level, string $message): string
	{
		return sprintf(
			'%s%s:%s',
			$this->dateFormat !== '' ? date($this->dateFormat) . ' ' : '',
			Str::upper($level),
			$message . PHP_EOL
		);
	}

	/**
	 * Format the message to write to the log.
	 */
	public function formatMessage(Arrayable|Jsonable|Stringable|array|string $message): string
	{
		$formattedMessage = (string) match (true) {
			is_array($message) => var_export($message, true),
			$message instanceof Arrayable => var_export($message->toArray(), true),
			$message instanceof Jsonable => $message->toJson(),
			default => $message,
		};

		return $this->replacePlaceholders
			? $this->formatMessagePlaceholders($formattedMessage)
			: $formattedMessage;
	}

	/**
	 * Replaces placeholders within a log message with corresponding context values.
	 */
	protected function formatMessagePlaceholders(string $message): string
	{
		if (empty($this->context)) {
			return $message;
		}

		$replacements = [];

		foreach ($this->context as $key => $value) {
			$placeholder = '{' . $key . '}';

			if (strpos($message, $placeholder) === false) {
				continue;
			}

			if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, "__toString"))) {
				$replacements[$placeholder] = $value;
			} elseif ($value instanceof DateTimeInterface) {
				$replacements[$placeholder] = $value->format($this->dateFormat);
			} elseif ($value instanceof UnitEnum) {
				$replacements[$placeholder] = $value instanceof BackedEnum ? $value->value : $value->name;
			} elseif (is_object($value)) {
				$replacements[$placeholder] = '[object ' . get_class($value) . ']';
			} elseif (is_array($value)) {
				$encodedValue = @json_encode(
					$value,
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION |
					JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
				);

				$replacements[$placeholder] = 'array' . ($encodedValue !== false ? $encodedValue : 'null');
			} else {
				$replacements[$placeholder] = '[' . gettype($value) . ']';
			}
		}

		return strtr($message, $replacements);
	}

	/**
	 * Log an informational message to the log.
	 */
	public function info(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::INFO, $message, $context);
	}

	/**
	 * Registers a callback handler to be executed when a log event is triggered.
	 *
	 * @throws \RuntimeException
	 */
	public function listen(Closure $callback): void
	{
		if (is_null($this->dispatcher)) {
			throw new RuntimeException('Events dispatcher has not been set.');
		}

		$this->dispatcher->listen(MessageLogged::class, $callback);
	}

	/**
	 * Log a message to the logs.
	 */
	public function log(mixed $level, string|Stringable $message, array $context = []): void
	{
		$this->writeLog($level, $message, $context);
	}

	/**
	 * Get the file logger instance.
	 */
	public function logger(): Filesystem
	{
		return $this->logger;
	}

	/**
	 * Log a notice to the log.
	 */
	public function notice(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Validates the configured log file path.
	 */
	protected function validateLogFile(): void
	{
		if ($this->logger->isDirectory($this->logFile)) {
			throw new UnexpectedValueException('Expected file path, got directory instead.');
		}

		$logFileDirectory = $this->logger->dirname($this->logFile);

		$this->logger->ensureDirectoryExists($logFileDirectory);

		if (! $this->logger->isDirectory($logFileDirectory)) {
			throw new UnexpectedValueException(
				sprintf('Directory "%s" not found or could not be created.', $logFileDirectory)
			);
		}
	}

	/**
	 * Log a warning message to the log.
	 */
	public function warning(string|Stringable $message, array $context = []): void
	{
		$this->writeLog(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Add context to all future log entries.
	 */
	public function withContext(array $context = []): static
	{
		$this->context = array_merge($this->context, $context);

		return $this;
	}

	/**
	 * Set the date format for all future log entries.
	 */
	public function withDateFormat(string $format): static
	{
		$this->dateFormat = $format;

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
	 * Pass log calls dynamically to the log writer.
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

		$this->logger->append($this->logFile, $this->formatLogMessage($level, $message));

		$this->fireLogEvent($level, $message, $context);
	}
}
