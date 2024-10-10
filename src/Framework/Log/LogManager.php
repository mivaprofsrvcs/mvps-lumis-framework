<?php

namespace MVPS\Lumis\Framework\Log;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use MVPS\Lumis\Framework\Contracts\Console\Application;
use MVPS\Lumis\Framework\Log\Traits\ParsesLogConfiguration;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

/**
 * @mixin \MVPS\Lumis\Framework\Log\Logger
 */
class LogManager implements LoggerInterface
{
	use ParsesLogConfiguration;

	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Console\Application
	 */
	protected Application $app;

	/**
	 * The array of resolved channels.
	 *
	 * @var array
	 */
	protected array $channels = [];

	/**
	 * The registered custom driver creators.
	 *
	 * @var array
	 */
	protected array $customCreators = [];

	/**
	 * The standard date format to use when writing logs.
	 *
	 * @var string
	 */
	protected string $dateFormat = 'Y-m-d H:i:s';

	/**
	 * The context shared across channels and stacks.
	 *
	 * @var array
	 */
	protected array $sharedContext = [];

	/**
	 * Create a new log manager instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 */
	public function alert(string|Stringable $message, array $context = []): void
	{
		$this->driver()->alert($message, $context);
	}

	/**
	 * Build an on-demand log channel.
	 */
	public function build(array $config): LoggerInterface
	{
		unset($this->channels['ondemand']);

		return $this->get('ondemand', $config);
	}

	/**
	 * Call a custom driver creator.
	 */
	protected function callCustomCreator(array $config): mixed
	{
		return $this->customCreators[$config['driver']]($this->app, $config);
	}

	/**
	 * Get a log channel instance.
	 */
	public function channel(string|null $channel = null): LoggerInterface
	{
		return $this->driver($channel);
	}

	/**
	 * Get the log connection configuration.
	 */
	protected function configurationFor(string $name): array
	{
		return $this->app['config']["logging.channels.$name"];
	}

	/**
	 * Create a custom log driver instance.
	 */
	protected function createCustomDriver(array $config): LoggerInterface
	{
		$via = $config['via'];

		$factory = is_callable($via)
			? $via
			: $this->app->make($via);

		return $factory($config);
	}

	/**
	 * Create an instance of the daily file log driver.
	 */
	protected function createDailyDriver(array $config): LoggerInterface
	{
		return new Monolog(
			$this->parseChannel($config),
			[
				$this->prepareHandler(
					new RotatingFileHandler(
						$config['path'],
						$config['days'] ?? 7,
						$this->level($config),
						$config['bubble'] ?? true,
						$config['permission'] ?? null,
						$config['locking'] ?? false
					),
					$config
				),
			],
			$config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []
		);
	}

	/**
	 * Create an emergency log handler to avoid white screens of death.
	 */
	protected function createEmergencyLogger(): LoggerInterface
	{
		$config = $this->configurationFor('emergency');

		$handler = new StreamHandler(
			$config['path'] ?? $this->app->storagePath() . '/logs/lumis.log',
			$this->level(['level' => 'debug'])
		);

		return new Logger(
			new Monolog('lumis', $this->prepareHandlers([$handler])),
			$this->app['events']
		);
	}

	/**
	 * Create an instance of the "error log" log driver.
	 */
	protected function createErrorlogDriver(array $config): LoggerInterface
	{
		return new Monolog(
			$this->parseChannel($config),
			[
				$this->prepareHandler(new ErrorLogHandler(
					$config['type'] ?? ErrorLogHandler::OPERATING_SYSTEM,
					$this->level($config)
				)),
			],
			$config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []
		);
	}

	/**
	 * Create an instance of any handler available in Monolog.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function createMonologDriver(array $config): LoggerInterface
	{
		if (! is_a($config['handler'], HandlerInterface::class, true)) {
			throw new InvalidArgumentException(
				$config['handler'] . ' must be an instance of ' . HandlerInterface::class
			);
		}

		collection($config['processors'] ?? [])->each(function ($processor) {
			$processor = $processor['processor'] ?? $processor;

			if (! is_a($processor, ProcessorInterface::class, true)) {
				throw new InvalidArgumentException(
					$processor . ' must be an instance of ' . ProcessorInterface::class
				);
			}
		});

		$with = array_merge(
			['level' => $this->level($config)],
			$config['with'] ?? [],
			$config['handler_with'] ?? []
		);

		$handler = $this->prepareHandler(
			$this->app->make($config['handler'], $with),
			$config
		);

		$processors = collection($config['processors'] ?? [])
			->map(fn ($processor) => $this->app->make(
				$processor['processor'] ?? $processor,
				$processor['with'] ?? []
			))
			->toArray();

		return new Monolog(
			$this->parseChannel($config),
			[$handler],
			$processors,
		);
	}

	/**
	 * Create an instance of the single file log driver.
	 */
	protected function createSingleDriver(array $config): LoggerInterface
	{
		return new Monolog(
			$this->parseChannel($config),
			[
				$this->prepareHandler(
					new StreamHandler(
						$config['path'],
						$this->level($config),
						$config['bubble'] ?? true,
						$config['permission'] ?? null,
						$config['locking'] ?? false
					),
					$config
				),
			],
			$config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []
		);
	}

	/**
	 * Create an instance of the Slack log driver.
	 */
	protected function createSlackDriver(array $config): LoggerInterface
	{
		return new Monolog(
			$this->parseChannel($config),
			[
				$this->prepareHandler(
					new SlackWebhookHandler(
						$config['url'],
						$config['channel'] ?? null,
						$config['username'] ?? 'Lumis',
						$config['attachment'] ?? true,
						$config['emoji'] ?? ':boom:',
						$config['short'] ?? false,
						$config['context'] ?? true,
						$this->level($config),
						$config['bubble'] ?? true,
						$config['exclude_fields'] ?? []
					),
					$config
				),
			],
			$config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []
		);
	}

	/**
	 * Create an aggregate log driver instance.
	 */
	protected function createStackDriver(array $config): LoggerInterface
	{
		if (is_string($config['channels'])) {
			$config['channels'] = explode(',', $config['channels']);
		}

		$handlers = collection($config['channels'])
			->flatMap(function ($channel) {
				return $channel instanceof LoggerInterface
					? $channel->getHandlers()
					: $this->channel($channel)->getHandlers();
			})
			->all();

		$processors = collection($config['channels'])
			->flatMap(function ($channel) {
				return $channel instanceof LoggerInterface
					? $channel->getProcessors()
					: $this->channel($channel)->getProcessors();
			})
			->all();

		if ($config['ignore_exceptions'] ?? false) {
			$handlers = [new WhatFailureGroupHandler($handlers)];
		}

		return new Monolog($this->parseChannel($config), $handlers, $processors);
	}

	/**
	 * Create an instance of the syslog log driver.
	 */
	protected function createSyslogDriver(array $config): LoggerInterface
	{
		return new Monolog(
			$this->parseChannel($config),
			[
				$this->prepareHandler(
					new SyslogHandler(
						Str::snake($this->app['config']['app.name'], '-'),
						$config['facility'] ?? LOG_USER,
						$this->level($config)
					),
					$config
				),
			],
			$config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []
		);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	public function critical(string|Stringable $message, array $context = []): void
	{
		$this->driver()->critical($message, $context);
	}

	/**
	 * Detailed debug information.
	 */
	public function debug(string|Stringable $message, array $context = []): void
	{
		$this->driver()->debug($message, $context);
	}

	/**
	 * Get a log driver instance.
	 */
	public function driver(string|null $driver = null): LoggerInterface
	{
		return $this->get($this->parseDriver($driver));
	}

	/**
	 * System is unusable.
	 */
	public function emergency(string|Stringable $message, array $context = []): void
	{
		$this->driver()->emergency($message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should
	 * typically be logged and monitored.
	 */
	public function error(string|Stringable $message, array $context = []): void
	{
		$this->driver()->error($message, $context);
	}

	/**
	 * Register a custom driver creator closure.
	 */
	public function extend(string $driver, Closure $callback): static
	{
		$this->customCreators[$driver] = $callback->bindTo($this, $this);

		return $this;
	}

	/**
	 * Flush the shared context.
	 */
	public function flushSharedContext(): static
	{
		$this->sharedContext = [];

		return $this;
	}

	/**
	 * Unset the given channel instance.
	 */
	public function forgetChannel(string|null $driver = null): void
	{
		$driver = $this->parseDriver($driver);

		if (isset($this->channels[$driver])) {
			unset($this->channels[$driver]);
		}
	}

	/**
	 * Get a Monolog formatter instance.
	 */
	protected function formatter(): FormatterInterface
	{
		return new LineFormatter(null, $this->dateFormat, true, true, true);
	}

	/**
	 * Attempt to get the log from the local cache.
	 */
	protected function get(string $name, array|null $config = null): LoggerInterface
	{
		try {
			return $this->channels[$name] ?? with($this->resolve($name, $config), function ($logger) use ($name) {
				$loggerWithContext = $this->tap($name, new Logger($logger, $this->app['events']))
					->withContext($this->sharedContext);

				return $this->channels[$name] = $loggerWithContext;
			});
		} catch (Throwable $e) {
			return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
				$logger->emergency('Unable to create configured logger. Using emergency logger.', [
					'exception' => $e,
				]);
			});
		}
	}

	/**
	 * Get all of the resolved log channels.
	 */
	public function getChannels(): array
	{
		return $this->channels;
	}

	/**
	 * Get the default log driver name.
	 */
	public function getDefaultDriver(): string|null
	{
		return $this->app['config']['logging.default'];
	}

	/**
	 * Get fallback log channel name.
	 */
	protected function getFallbackChannelName(): string
	{
		return $this->app->bound('env')
			? $this->app->environment()
			: 'production';
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 */
	public function info(string|Stringable $message, array $context = []): void
	{
		$this->driver()->info($message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 */
	public function log(mixed $level, string|Stringable $message, array $context = []): void
	{
		$this->driver()->log($level, $message, $context);
	}

	/**
	 * Normal but significant events.
	 */
	public function notice(string|Stringable $message, array $context = []): void
	{
		$this->driver()->notice($message, $context);
	}

	/**
	 * Parse the driver name.
	 */
	protected function parseDriver(string|null $driver): string|null
	{
		return $driver ??= $this->getDefaultDriver();
	}

	/**
	 * Parse the given tap class string into a class name and arguments string.
	 */
	protected function parseTap(string $tap): array
	{
		return str_contains($tap, ':')
			? explode(':', $tap, 2)
			: [$tap, ''];
	}

	/**
	 * Prepare the handler for usage by Monolog.
	 */
	protected function prepareHandler(HandlerInterface $handler, array $config = []): HandlerInterface
	{
		if (isset($config['action_level'])) {
			$handler = new FingersCrossedHandler(
				$handler,
				$this->actionLevel($config),
				0,
				true,
				$config['stop_buffering'] ?? true
			);
		}

		if (! $handler instanceof FormattableHandlerInterface) {
			return $handler;
		}

		if (! isset($config['formatter'])) {
			$handler->setFormatter($this->formatter());
		} elseif ($config['formatter'] !== 'default') {
			$handler->setFormatter(
				$this->app->make($config['formatter'], $config['formatter_with'] ?? [])
			);
		}

		return $handler;
	}

	/**
	 * Prepare the handlers for usage by Monolog.
	 */
	protected function prepareHandlers(array $handlers): array
	{
		foreach ($handlers as $key => $handler) {
			$handlers[$key] = $this->prepareHandler($handler);
		}

		return $handlers;
	}

	/**
	 * Resolve the given log instance by name.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolve(string $name, array|null $config = null): LoggerInterface
	{
		$config ??= $this->configurationFor($name);

		if (is_null($config)) {
			throw new InvalidArgumentException("Log [$name] is not defined.");
		}

		if (isset($this->customCreators[$config['driver']])) {
			return $this->callCustomCreator($config);
		}

		$driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

		if (method_exists($this, $driverMethod)) {
			return $this->{$driverMethod}($config);
		}

		throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
	}

	/**
	 * Set the default log driver name.
	 */
	public function setDefaultDriver(string $name): void
	{
		$this->app['config']['logging.default'] = $name;
	}

	/**
	 * Share context across channels and stacks.
	 */
	public function shareContext(array $context): static
	{
		foreach ($this->channels as $channel) {
			$channel->withContext($context);
		}

		$this->sharedContext = array_merge($this->sharedContext, $context);

		return $this;
	}

	/**
	 * The context shared across channels and stacks.
	 */
	public function sharedContext(): array
	{
		return $this->sharedContext;
	}

	/**
	 * Flush the log context on all currently resolved channels.
	 */
	public function withoutContext(): static
	{
		foreach ($this->channels as $channel) {
			if (method_exists($channel, 'withoutContext')) {
				$channel->withoutContext();
			}
		}

		return $this;
	}

	/**
	 * Set the application instance used by the manager.
	 */
	public function setApplication(Application $app): static
	{
		$this->app = $app;

		return $this;
	}

	/**
	 * Create a new, on-demand aggregate logger instance.
	 */
	public function stack(array $channels, string|null $channel = null): LoggerInterface
	{
		return (new Logger(
			$this->createStackDriver(compact('channels', 'channel')),
			$this->app['events']
		))->withContext($this->sharedContext);
	}

	/**
	 * Apply the configured taps for the logger.
	 */
	protected function tap(string $name, Logger $logger): Logger
	{
		foreach ($this->configurationFor($name)['tap'] ?? [] as $tap) {
			[$class, $arguments] = $this->parseTap($tap);

			$this->app->make($class)->__invoke($logger, ...explode(',', $arguments));
		}

		return $logger;
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 */
	public function warning(string|Stringable $message, array $context = []): void
	{
		$this->driver()->warning($message, $context);
	}

	/**
	 * Dynamically call the default driver instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->driver()->$method(...$parameters);
	}
}
