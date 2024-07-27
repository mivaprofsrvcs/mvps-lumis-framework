<?php

namespace MVPS\Lumis\Framework\Providers;

use Closure;
use MVPS\Lumis\Framework\Console\Application as ConsoleApplication;
use MVPS\Lumis\Framework\Contracts\Framework\Application;

abstract class ServiceProvider
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	/**
	 * All of the registered booted callbacks.
	 */
	protected array $bootedCallbacks = [];

	/**
	 * All of the registered booting callbacks.
	 *
	 * @var array
	 */
	protected array $bootingCallbacks = [];

	/**
	 * Create a new service provider instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Add the given provider to the application's provider bootstrap file.
	 */
	public static function addProviderToBootstrapFile(string $provider, string|null $path = null): bool
	{
		$path ??= app()->getBootstrapProvidersPath();

		if (! file_exists($path)) {
			return false;
		}

		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($path, true);
		}

		$providers = collection(require $path)
			->merge([$provider])
			->unique()
			->sort()
			->values()
			->map(fn ($p) => "\t" . $p . '::class,')
			->implode(PHP_EOL);

		file_put_contents($path, static::providerBootstrapFileContent($providers) . PHP_EOL);

		return true;
	}

	/**
	 * Register a booted callback to be run after the "boot" method is called.
	 */
	public function booted(Closure $callback): void
	{
		$this->bootedCallbacks[] = $callback;
	}

	/**
	 * Register a booting callback to be run before the "boot" method is called.
	 */
	public function booting(Closure $callback): void
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Attaches a callback to be executed after a service is resolved by the container.
	 */
	protected function callAfterResolving(string $name, callable $callback): void
	{
		$this->app->afterResolving($name, $callback);

		if ($this->app->resolved($name)) {
			$callback($this->app->make($name), $this->app);
		}
	}

	/**
	 * Call the registered booted callbacks.
	 */
	public function callBootedCallbacks(): void
	{
		$index = 0;

		while ($index < count($this->bootedCallbacks)) {
			$this->app->call($this->bootedCallbacks[$index]);

			$index++;
		}
	}

	/**
	 * Call the registered booting callbacks.
	 */
	public function callBootingCallbacks(): void
	{
		$index = 0;

		while ($index < count($this->bootingCallbacks)) {
			$this->app->call($this->bootingCallbacks[$index]);

			$index++;
		}
	}

	/**
	 * Register the package's custom Lumis commands.
	 */
	public function commands(mixed $commands): void
	{
		$commands = is_array($commands) ? $commands : func_get_args();

		ConsoleApplication::starting(function ($lumis) use ($commands) {
			$lumis->resolveCommands($commands);
		});
	}

	/**
	 * Get the default providers for a Lumis application.
	 */
	public static function defaultProviders(): DefaultProviders
	{
		return new DefaultProviders;
	}

	/**
	 * Register a view file namespace.
	 */
	protected function loadViewsFrom(string|array $path, string $namespace): void
	{
		$this->callAfterResolving('view', function ($view) use ($path, $namespace) {
			if (isset($this->app->config['view']['paths']) && is_array($this->app->config['view']['paths'])) {
				foreach ($this->app->config['view']['paths'] as $viewPath) {
					$appPath = $viewPath . '/vendor/' . $namespace;

					if (is_dir($appPath)) {
						$view->addNamespace($namespace, $appPath);
					}
				}
			}

			$view->addNamespace($namespace, $path);
		});
	}

	/**
	 * Get the provider bootstrap file content.
	 */
	protected static function providerBootstrapFileContent(string $providers): string
	{
		return implode("\n", [
			"<?php\n",
			'return [',
			$providers,
			'];'
		]);
	}

	/**
	 * Get the services provided by the provider.
	 */
	public function provides(): array
	{
		return [];
	}

	/**
	 * Register any application services.
	 */
	public function register(): void
	{
	}
}
