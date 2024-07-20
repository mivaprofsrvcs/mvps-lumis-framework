<?php

namespace MVPS\Lumis\Framework;

use Closure;
use Composer\Autoload\ClassLoader;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Configuration\ApplicationBuilder;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Configuration\CachesConfiguration;
use MVPS\Lumis\Framework\Contracts\Console\Kernel as ConsoleKernelContract;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as HttpKernelContract;
use MVPS\Lumis\Framework\Debugging\DumperServiceProvider;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Providers\ServiceProvider;
use MVPS\Lumis\Framework\Routing\RoutingServiceProvider;
use MVPS\Lumis\Framework\Support\Env;
use MVPS\Lumis\Framework\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application extends Container implements CachesConfiguration
{
	/**
	 * The base configuration directory path.
	 *
	 * @var string
	 */
	public const FRAMEWORK_CONFIG_PATH = __DIR__ . '/config';

	/**
	 * The base configuration directory path.
	 *
	 * @var string
	 */
	public const FRAMEWORK_RESOURCES_PATH = __DIR__ . '/resources';

	/**
	 * The Lumis framework version
	 *
	 * @var string
	 */
	protected const VERSION = '2.0.0';

	/**
	 * The prefixes of absolute cache paths for use during normalization.
	 *
	 * @var string[]
	 */
	protected array $absoluteCachePathPrefixes = ['/', '\\'];

	/**
	 * The "app" directory path for the application.
	 *
	 * @var string
	 */
	protected string $appPath = '';

	/**
	 * The base directory path for the application.
	 *
	 * @var string
	 */
	protected string $basePath = '';

	/**
	 * Indicates if the application is in the "booted" state.
	 *
	 * @var bool
	 */
	protected bool $booted = false;

	/**
	 * The array of booted callbacks.
	 *
	 * @var callable[]
	 */
	protected array $bootedCallbacks = [];

	/**
	 * The array of booting callbacks.
	 *
	 * @var callable[]
	 */
	protected array $bootingCallbacks = [];

	/**
	 * The bootstrap directory path for the application.
	 *
	 * @var string
	 */
	protected string $bootstrapPath = '';

	/**
	 * The configuration files path for the application.
	 *
	 * @var string
	 */
	protected string $configPath = '';

	/**
	 * The environment file to load during bootstrapping.
	 *
	 * @var string
	 */
	protected string $environmentFile = '.env';

	/**
	 * The environment (.env) file path for the application.
	 *
	 * @var string
	 */
	protected string $environmentPath = '';

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected bool $hasBeenBootstrapped = false;

	/**
	 * Indicates if the application is running in the console.
	 *
	 * @var bool|null
	 */
	protected bool|null $isRunningInConsole = null;

	/**
	 * The loaded service providers.
	 *
	 * @var array
	 */
	protected array $loadedProviders = [];

	/**
	 * Indicates if the framework's base configuration should be merged.
	 *
	 * @var bool
	 */
	protected bool $mergeFrameworkConfiguration = true;

	/**
	 * The application namespace.
	 *
	 * @var string
	 */
	protected string $namespace = '';

	/**
	 * The public web path for the application.
	 *
	 * @var string
	 */
	protected string $publicPath = '';

	/**
	 * The list of registered callbacks.
	 *
	 * @var callable[]
	 */
	protected array $registeredCallbacks = [];

	 /**
	 * All of the registered service providers.
	 *
	 * @var array<string, \MVPS\Lumis\Framework\Providers\ServiceProvider>
	 */
	protected array $serviceProviders = [];

	/**
	 * The storage directory path for the application.
	 *
	 * @var string
	 */
	protected string $storagePath = '';

	/**
	 * The tasks directory path for the application.
	 *
	 * @var string
	 */
	protected string $taskPath = '';

	/**
	 * Create a new Lumis application instance.
	 */
	public function __construct(string|null $basePath = null)
	{
		if ($basePath) {
			$this->setBasePath($basePath);
		}

		$this->registerBaseBindings();
		$this->registerBaseServiceProviders();
		$this->registerCoreContainerAliases();
	}

	/**
	 * Add new prefix to list of absolute path prefixes.
	 */
	public function addAbsoluteCachePathPrefix(string $prefix): static
	{
		$this->absoluteCachePathPrefixes[] = $prefix;

		return $this;
	}

	/**
	 * Get the base path of the application.
	 */
	public function basePath(string $path = ''): string
	{
		return $this->joinPaths($this->basePath, $path);
	}

	/**
	 * Get the version number of the application.
	 */
	protected function bindPathsInContainer(): void
	{
		$this->instance('path', $this->path());
		$this->instance('path.base', $this->basePath());
		$this->instance('path.config', $this->configPath());
		$this->instance('path.public', $this->publicPath());
		$this->instance('path.resources', $this->resourcePath());
		$this->instance('path.storage', $this->storagePath());
		$this->instance('path.tasks', $this->taskPath());

		$this->useBootstrapPath($this->basePath('bootstrap'));
	}

	/**
	 * Boot the application's service providers.
	 */
	public function boot(): void
	{
		if ($this->isBooted()) {
			return;
		}

		$this->callAppCallbacks($this->bootingCallbacks);

		array_walk($this->serviceProviders, function ($provider) {
			$this->bootProvider($provider);
		});

		$this->booted = true;

		$this->callAppCallbacks($this->bootedCallbacks);
	}

	/**
	 * Boot the given service provider.
	 */
	protected function bootProvider(ServiceProvider $provider): void
	{
		$provider->callBootingCallbacks();

		if (method_exists($provider, 'boot')) {
			$this->call([$provider, 'boot']);
		}

		$provider->callBootedCallbacks();
	}

	/**
	 * Register a new "booted" listener.
	 */
	public function booted(callable $callback): void
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) {
			$callback($this);
		}
	}

	/**
	 * Register a new boot listener.
	 */
	public function booting(callable $callback): void
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Get the path to the bootstrap directory.
	 */
	public function bootstrapPath(string $path = ''): string
	{
		return $this->joinPaths($this->bootstrapPath, $path);
	}

	/**
	 * Run the given array of bootstrap classes.
	 */
	public function bootstrapWith(array $bootstrappers): void
	{
		$this->hasBeenBootstrapped = true;

		foreach ($bootstrappers as $bootstrapper) {
			$this->make($bootstrapper)->bootstrap($this);
		}
	}

	/**
	 * Call the booting callbacks for the application.
	 */
	protected function callAppCallbacks(array &$callbacks): void
	{
		$index = 0;

		while ($index < count($callbacks)) {
			$callbacks[$index]($this);

			$index++;
		}
	}

	/**
	 * Get the path to the application configuration files.
	 */
	public function configPath(string $path = ''): string
	{
		return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
	}

	/**
	 * Determine if the application configuration is cached.
	 */
	public function configurationIsCached(): bool
	{
		return is_file($this->getCachedConfigPath());
	}

	/**
	 * Begin configuring a new Lumis application instance.
	 */
	public static function configure(string|null $basePath = null): ApplicationBuilder
	{
		$basePath = match (true) {
			is_string($basePath) => $basePath,
			default => static::inferBasePath(),
		};

		return (new ApplicationBuilder(new static($basePath)))
			->withKernels()
			->withCommands()
			->withProviders();
	}

	/**
	 * Detect the application's current environment.
	 */
	public function detectEnvironment(Closure $callback): string
	{
		$args = $_SERVER['argv'] ?? null;

		return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
	}

	/**
	 * Get the current application environment.
	 */
	public function environment(string|array ...$environments): string|bool
	{
		if (count($environments) > 0) {
			$patterns = is_array($environments[0]) ? $environments[0] : $environments;

			return Str::is($patterns, $this['env']);
		}

		return $this['env'];
	}

	/**
	 * Get the environment file the application is using.
	 */
	public function environmentFile(): string
	{
		return $this->environmentFile ?: '.env';
	}

	/**
	 * Get the fully qualified path to the environment file.
	 */
	public function environmentFilePath(): string
	{
		return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
	}

	/**
	 * Get the path to the environment file directory.
	 */
	public function environmentPath(): string
	{
		return $this->environmentPath ?: $this->basePath;
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 */
	public function flush(): void
	{
		parent::flush();

		$this->bootedCallbacks = [];
		$this->bootingCallbacks = [];
		$this->buildStack = [];
		$this->loadedProviders = [];
		$this->reboundCallbacks = [];
		$this->serviceProviders = [];
	}

	public function frameworkConigPath(): string
	{
		return static::FRAMEWORK_CONFIG_PATH;
	}

	public function frameworkResourcePath(): string
	{
		return static::FRAMEWORK_RESOURCES_PATH;
	}

	/**
	 * Get the path to the service provider list in the bootstrap directory.
	 */
	public function getBootstrapProvidersPath(): string
	{
		return $this->bootstrapPath('providers.php');
	}

	/**
	 * Get the path to the configuration cache file.
	 */
	public function getCachedConfigPath(): string
	{
		return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
	}

	/**
	 * Get the path to the cached services.php file.
	 */
	public function getCachedServicesPath(): string
	{
		return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
	}

	/**
	 * Get the application namespace.
	 *
	 * @throws \RuntimeException
	 */
	public function getNamespace(): string
	{
		if ($this->namespace !== '') {
			return $this->namespace;
		}

		$composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
			foreach ((array) $path as $pathChoice) {
				if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
					return $this->namespace = $namespace;
				}
			}
		}

		throw new RuntimeException('Unable to detect application namespace.');
	}

	/**
	 * Get the registered service provider instance if it exists.
	 */
	public function getProvider(ServiceProvider|string $provider): ServiceProvider|null
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return $this->serviceProviders[$name] ?? null;
	}

	/**
	 * Get the registered service provider instances if any exist.
	 */
	public function getProviders(ServiceProvider|string $provider): array
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return Arr::where($this->serviceProviders, fn ($value) => $value instanceof $name);
	}

	/**
	 * Handle the incoming Lumis command.
	 */
	public function handleCommand(InputInterface $input): int
	{
		$kernel = $this->make(ConsoleKernelContract::class);

		$status = $kernel->handle($input, new ConsoleOutput);

		return $status;
	}

	/**
	 * Handle the incoming HTTP request and send the response to the browser.
	 */
	public function handleRequest(Request $request): void
	{
		$kernel = $this->make(HttpKernelContract::class);

		$response = $kernel->handle($request)
			->send();
	}

	/**
	 * Handle the incoming task.
	 */
	public function handleTask(): void
	{
		$kernel = $this->make(ConsoleKernelContract::class);

		$kernel->handleTask();
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 */
	public function hasBeenBootstrapped(): bool
	{
		return $this->hasBeenBootstrapped;
	}

	/**
	 * Infer the application's base directory from the environment.
	 */
	public static function inferBasePath(): string
	{
		return match (true) {
			isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
			default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
		};
	}

	/**
	 * Determine if the application has booted.
	 */
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	 * Determine if the application is in the local environment.
	 */
	public function isLocal(): bool
	{
		return $this['env'] === 'local';
	}

	/**
	 * Determine if the application is in the production environment.
	 */
	public function isProduction(): bool
	{
		return $this['env'] === 'production';
	}

	/**
	 * Join the given paths together.
	 */
	public function joinPaths(string $basePath, string $path = ''): string
	{
		return $basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
	}

	/**
	 * Mark the given provider as registered.
	 */
	protected function markAsRegistered(ServiceProvider $provider): void
	{
		$class = get_class($provider);

		$this->serviceProviders[$class] = $provider;

		$this->loadedProviders[$class] = true;
	}

	/**
	 * Normalize a relative or absolute path to a cache file.
	 */
	protected function normalizeCachePath(string $key, string $default): string
	{
		if (is_null($env = Env::get($key))) {
			return $this->bootstrapPath($default);
		}

		return Str::startsWith($env, $this->absoluteCachePathPrefixes)
			? $env
			: $this->basePath($env);
	}

	/**
	 * Get the path to the application "app" directory.
	 */
	public function path(string $path = ''): string
	{
		return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
	}

	/**
	 * Get the path to the public / web directory.
	 */
	public function publicPath(string $path = ''): string
	{
		return $this->joinPaths($this->publicPath ?: $this->basePath('httpdocs'), $path);
	}

	/**
	 * Register a service provider with the application.
	 */
	public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
	{
		$registered = $this->getProvider($provider);

		if ($registered && ! $force) {
			return $registered;
		}

		if (is_string($provider)) {
			$provider = $this->resolveProvider($provider);
		}

		$provider->register();

		// If there are bindings / singletons set as properties on the provider we
		// will spin through them and register them with the application, which
		// serves as a convenience layer while registering a lot of bindings.
		if (property_exists($provider, 'bindings')) {
			foreach ($provider->bindings as $key => $value) {
				$this->bind($key, $value);
			}
		}

		if (property_exists($provider, 'singletons')) {
			foreach ($provider->singletons as $key => $value) {
				$key = is_int($key) ? $value : $key;

				$this->singleton($key, $value);
			}
		}

		$this->markAsRegistered($provider);

		// If the application has already booted, we will call this boot method
		// on the provider class so it has an opportunity to do its boot logic.
		if ($this->isBooted()) {
			$this->bootProvider($provider);
		}

		return $provider;
	}

	/**
	 * Register the basic bindings into the container.
	 */
	protected function registerBaseBindings(): void
	{
		static::setInstance($this);

		$this->instance('app', $this);

		$this->instance(Container::class, $this);
	}

	/**
	 * Register all of the base service providers.
	 */
	protected function registerBaseServiceProviders(): void
	{
		$this->register(new RoutingServiceProvider($this));
	}

	/**
	 * Register all of the configured providers.
	 */
	public function registerConfiguredProviders(): void
	{
		$providers = $this->make('config')
			->get('app.providers');

		foreach ($providers as $provider) {
			$this->register($provider);
		}

		$this->callAppCallbacks($this->registeredCallbacks);
	}

	/**
	 * Register the core class aliases in the container.
	 */
	public function registerCoreContainerAliases(): void
	{
		$coreAliases = [
			'app' => [static::class],
			'config' => [
				\MVPS\Lumis\Framework\Configuration\Repository::class,
				\MVPS\Lumis\Framework\Contracts\Configuration\Repository::class,
			],
			'files' => [\MVPS\Lumis\Framework\Filesystem\Filesystem::class],
			'request' => [\MVPS\Lumis\Framework\Http\Request::class],
			'router' => [\MVPS\Lumis\Framework\Routing\Router::class],
			'url' => [
				\MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator::class,
				\MVPS\Lumis\Framework\Routing\UrlGenerator::class,
			],
			// TODO: Implement these
			// 'encrypter' => [Encrypter::class],
			// 'view' => [View\Factory::class],
		];

		foreach ($coreAliases as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Register a new registered listener.
	 */
	public function registered(callable $callback): void
	{
		$this->registeredCallbacks[] = $callback;
	}

	/**
	 * Resolve a service provider instance from the class name.
	 */
	public function resolveProvider(string $provider): ServiceProvider
	{
		return new $provider($this);
	}

	/**
	 * Get the path to the resources directory.
	 */
	public function resourcePath(string $path = ''): string
	{
		return $this->joinPaths($this->basePath('resources'), $path);
	}

	/**
	 * Determine if the application is running in the console.
	 */
	public function runningInConsole(): bool
	{
		if ($this->isRunningInConsole === null) {
			$this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ??
				(PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
		}

		return $this->isRunningInConsole;
	}

	/**
	 * Determine if the application is running unit tests.
	 */
	public function runningUnitTests(): bool
	{
		return $this->bound('env') && $this['env'] === 'testing';
	}

	/**
	 * Set the base path for the application.
	 */
	public function setBasePath(string $basePath): static
	{
		$this->basePath = rtrim($basePath, '\/');

		$this->bindPathsInContainer();

		return $this;
	}

	/**
	 * Determine if the framework's base configuration should be merged.
	 */
	public function shouldMergeFrameworkConfiguration(): bool
	{
		return $this->mergeFrameworkConfiguration;
	}

	/**
	 * Get the path to the storage directory.
	 */
	public function storagePath(string $path = ''): string
	{
		return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
	}

	/**
	 * Get the path to the tasks directory.
	 */
	public function taskPath(string $path = ''): string
	{
		return $this->joinPaths($this->taskPath ?: $this->basePath('tasks'), $path);
	}

	/**
	 * Set the bootstrap file directory.
	 */
	public function useBootstrapPath(string $path): static
	{
		$this->bootstrapPath = $path;

		$this->instance('path.bootstrap', $path);

		return $this;
	}

	/**
	 * Get the version number of the application.
	 */
	public function version(): string
	{
		return static::VERSION;
	}
}
