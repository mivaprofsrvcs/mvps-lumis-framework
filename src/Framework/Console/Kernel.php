<?php

namespace MVPS\Lumis\Framework\Console;

use Carbon\CarbonInterval;
use Closure;
use DateTimeInterface;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\HandleExceptions;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Bootstrap\SetRequestForConsole;
use MVPS\Lumis\Framework\Console\Application as ConsoleApplication;
use MVPS\Lumis\Framework\Contracts\Console\Kernel as KernelContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Events\Terminating;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

class Kernel implements KernelContract
{
	use InteractsWithTime;

	/**
	 * The application implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array<string>
	 */
	protected array $bootstrappers = [
		LoadEnvironmentVariables::class,
		LoadConfiguration::class,
		HandleExceptions::class,
		SetRequestForConsole::class,
		RegisterProviders::class,
		BootProviders::class,
	];

	/**
	 * All of the registered command duration handlers.
	 *
	 * @var array
	 */
	protected array $commandLifecycleDurationHandlers = [];

	/**
	 * The paths where Lumis commands should be automatically discovered.
	 *
	 * @var array
	 */
	protected array $commandPaths = [];

	/**
	 * The paths where Lumis "routes" should be automatically discovered.
	 *
	 * @var array
	 */
	protected array $commandRoutePaths = [];

	/**
	 * The Lumis commands provided by the application.
	 *
	 * @var array
	 */
	protected array $commands = [];

	/**
	 * Indicates if the Closure commands have been loaded.
	 *
	 * @var bool
	 */
	protected bool $commandsLoaded = false;

	/**
	 * When the currently handled command started.
	 *
	 * @var \Illuminate\Support\Carbon|null
	 */
	protected $commandStartedAt;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected Dispatcher $events;

	/**
	 * The commands paths that have been "loaded".
	 *
	 * @var array
	 */
	protected array $loadedPaths = [];

	/**
	 * The Lumis console application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Console\Application|null
	 */
	protected ConsoleApplication|null $lumis = null;

	/**
	 * The Symfony event dispatcher implementation.
	 *
	 * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null
	 */
	protected EventDispatcherInterface|null $symfonyDispatcher = null;

	/**
	 * Create a new console kernel instance.
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		if (! defined('LUMIS_BINARY')) {
			define('LUMIS_BINARY', 'lumis');
		}

		$this->app = $app;
		$this->events = $events;

		$this->app->booted(function () {
			$this->rerouteSymfonyCommandEvents();
		});
	}

	/**
	 * Get all of the commands registered with the console.
	 */
	public function all(): array
	{
		$this->bootstrap();

		return $this->getLumis()->all();
	}

	/**
	 * Set the Lumis commands provided by the application.
	 */
	public function addCommands(array $commands): static
	{
		$this->commands = array_values(array_unique(array_merge($this->commands, $commands)));

		return $this;
	}

	/**
	 * Set the paths that should have their Lumis commands automatically discovered.
	 */
	public function addCommandPaths(array $paths): static
	{
		$this->commandPaths = array_values(array_unique(array_merge($this->commandPaths, $paths)));

		return $this;
	}

	/**
	 * Set the paths that should have their Lumis "routes" automatically discovered.
	 */
	public function addCommandRoutePaths(array $paths): static
	{
		$this->commandRoutePaths = array_values(array_unique(array_merge($this->commandRoutePaths, $paths)));

		return $this;
	}

	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void
	{
		if (! $this->app->hasBeenBootstrapped()) {
			$this->app->bootstrapWith($this->bootstrappers());
		}

		$this->app->loadDeferredProviders();

		if (! $this->commandsLoaded) {
			$this->commands();

			if ($this->shouldDiscoverCommands()) {
				$this->discoverCommands();
			}

			$this->commandsLoaded = true;
		}
	}

	/**
	 * Get the bootstrap classes for the application.
	 */
	protected function bootstrappers(): array
	{
		return $this->bootstrappers;
	}

	/**
	 * Bootstrap the application for a task.
	 */
	public function bootstrapTask(): void
	{
		if ($this->app->hasBeenBootstrapped()) {
			return;
		}

		$this->app->bootstrapWith($this->bootstrappers());
	}

	/**
	 * Bootstrap the application without booting service providers.
	 */
	public function bootstrapWithoutBootingProviders(): void
	{
		$this->app->bootstrapWith(
			collection($this->bootstrappers())
				->reject(fn ($bootstrapper) => $bootstrapper === BootProviders::class)
				->all()
		);
	}

	/**
	 * Run a Lumis console command by name.
	 */
	public function call(string $command, array $parameters = [], OutputInterface|null $outputBuffer = null): int
	{
		if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
			$this->bootstrapWithoutBootingProviders();
		}

		$this->bootstrap();

		return $this->getLumis()->call($command, $parameters, $outputBuffer);
	}

	/**
	 * Register a closure based command with the application.
	 */
	public function command(string $signature, Closure $callback): ClosureCommand
	{
		$command = new ClosureCommand($signature, $callback);

		ConsoleApplication::starting(fn ($lumis) => $lumis->add($command));

		return $command;
	}

	/**
	 * Extract the command class name from the given file path.
	 */
	protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
	{
		return $namespace . str_replace(
			['/', '.php'],
			['\\', ''],
			Str::after($file->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
		);
	}

	/**
	 * Register the commands for the application.
	 */
	protected function commands(): void
	{
		//
	}

	/**
	 * When the command being handled started.
	 */
	public function commandStartedAt(): Carbon|null
	{
		return $this->commandStartedAt;
	}

	/**
	 * Discover the commands that should be automatically loaded.
	 */
	protected function discoverCommands(): void
	{
		foreach ($this->commandPaths as $path) {
			$this->load($path);
		}

		foreach ($this->commandRoutePaths as $path) {
			if (! file_exists($path)) {
				continue;
			}

			require $path;
		}
	}

	/**
	 * Get the Lumis console application instance.
	 */
	protected function getLumis(): ConsoleApplication
	{
		if (is_null($this->lumis)) {
			$this->lumis = (new ConsoleApplication($this->app, $this->events, $this->app->version()))
				->resolveCommands($this->commands)
				->setContainerCommandLoader();

			if ($this->symfonyDispatcher instanceof EventDispatcher) {
				$this->lumis->setDispatcher($this->symfonyDispatcher);
				$this->lumis->setSignalsToDispatchEvent();
			}
		}

		return $this->lumis;
	}

	/**
	 * Run the console application.
	 */
	public function handle(InputInterface $input, OutputInterface|null $output = null): int
	{
		$this->commandStartedAt = Carbon::now();

		try {
			if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
				$this->bootstrapWithoutBootingProviders();
			}

			$this->bootstrap();

			return $this->getLumis()->run($input, $output);
		} catch (Throwable $e) {
			$this->reportException($e);

			$this->renderException($output, $e);

			return 1;
		}
	}

	/**
	 * Run the console application for a task.
	 */
	public function handleTask(): void
	{
		$this->bootstrapTask();
	}

	/**
	 * Register all of the commands in the given directory.
	 */
	protected function load(array|string $paths): void
	{
		$paths = array_filter(
			array_unique(Arr::wrap($paths)),
			fn ($path) => is_dir($path)
		);

		if (empty($paths)) {
			return;
		}

		$this->loadedPaths = array_values(
			array_unique(array_merge($this->loadedPaths, $paths))
		);

		$namespace = $this->app->getNamespace();

		foreach (Finder::create()->in($paths)->files() as $file) {
			$command = $this->commandClassFromFile($file, $namespace);

			if (is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract()) {
				ConsoleApplication::starting(fn ($lumis) => $lumis->resolve($command));
			}
		}
	}

	/**
	 * Get the output for the last run command.
	 */
	public function output(): string
	{
		$this->bootstrap();

		return $this->getLumis()->output();
	}

	/**
	 * Register the given command with the console application.
	 */
	public function registerCommand(SymfonyCommand $command): void
	{
		$this->getLumis()->add($command);
	}

	/**
	 * Render the given exception.
	 */
	protected function renderException(OutputInterface $output, Throwable $e): void
	{
		$this->app[ExceptionHandler::class]->renderForConsole($output, $e);
	}

	/**
	 * Report the exception to the exception handler.
	 */
	protected function reportException(Throwable $e): void
	{
		$this->app[ExceptionHandler::class]->report($e);
	}

	/**
	 * Re-route the Symfony command events to their Lumis counterparts.
	 */
	public function rerouteSymfonyCommandEvents(): static
	{
		if (! is_null($this->symfonyDispatcher)) {
			return $this;
		}

		$this->symfonyDispatcher = new EventDispatcher;

		$this->symfonyDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
			$this->events->dispatch(
				new CommandStarting(
					$event->getCommand()->getName(),
					$event->getInput(),
					$event->getOutput()
				)
			);
		});

		$this->symfonyDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
			$this->events->dispatch(
				new CommandFinished(
					$event->getCommand()->getName(),
					$event->getInput(),
					$event->getOutput(),
					$event->getExitCode()
				)
			);
		});

		return $this;
	}

	/**
	 * Set the Lumis console application instance.
	 */
	public function setLumis(ConsoleApplication|null $lumis): void
	{
		$this->lumis = $lumis;
	}

	/**
	 * Determine if the kernel should discover commands.
	 */
	protected function shouldDiscoverCommands(): bool
	{
		return get_class($this) === __CLASS__;
	}

	/**
	 * Terminate the console application.
	 */
	public function terminate(InputInterface $input, int $status): void
	{
		$this->events->dispatch(new Terminating);

		$this->app->terminate();

		if (is_null($this->commandStartedAt)) {
			return;
		}

		$this->commandStartedAt->setTimezone($this->app['config']->get('app.timezone') ?? 'UTC');

		foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
			$end ??= Carbon::now();

			if ($this->commandStartedAt->diffInMilliseconds($end) > $threshold) {
				$handler($this->commandStartedAt, $input, $status);
			}
		}

		$this->commandStartedAt = null;
	}

	/**
	 * Register a callback to be invoked when the command lifecycle duration
	 * exceeds a given amount of time.
	 */
	public function whenCommandLifecycleIsLongerThan(
		DateTimeInterface|CarbonInterval|float|int $threshold,
		callable $handler
	): void {
		$threshold = $threshold instanceof DateTimeInterface
			? $this->secondsUntil($threshold) * 1000
			: $threshold;

		$threshold = $threshold instanceof CarbonInterval
			? $threshold->totalMilliseconds
			: $threshold;

		$this->commandLifecycleDurationHandlers[] = [
			'threshold' => $threshold,
			'handler' => $handler,
		];
	}
}
