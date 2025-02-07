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

namespace MVPS\Lumis\Framework\Providers;

use Closure;
use MVPS\Lumis\Framework\Console\Application as ConsoleApplication;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\View\Compilers\BladeCompiler;

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
	 * The paths that should be published.
	 *
	 * @var array
	 */
	public static array $publishes = [];

	/**
	 * The paths that should be published by group.
	 *
	 * @var array
	 */
	public static array $publishGroups = [];

	/**
	 * The migration paths available for publishing.
	 *
	 * @var array
	 */
	protected static array $publishableMigrationPaths = [];

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
	 * Add a publish group / tag to the service provider.
	 */
	protected function addPublishGroup(string $group, array $paths): void
	{
		if (! array_key_exists($group, static::$publishGroups)) {
			static::$publishGroups[$group] = [];
		}

		static::$publishGroups[$group] = array_merge(
			static::$publishGroups[$group],
			$paths
		);
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

		ConsoleApplication::starting(fn ($lumis) => $lumis->resolveCommands($commands));
	}

	/**
	 * Get the default providers for a Lumis application.
	 */
	public static function defaultProviders(): DefaultProviders
	{
		return new DefaultProviders;
	}

	/**
	 * Ensure the publish array for the service provider is initialized.
	 */
	protected function ensurePublishArrayInitialized(string $class): void
	{
		if (! array_key_exists($class, static::$publishes)) {
			static::$publishes[$class] = [];
		}
	}

	/**
	 * Register a JSON translation file path.
	 */
	protected function loadJsonTranslationsFrom(string $path): void
	{
		$this->callAfterResolving('translator', function ($translator) use ($path) {
			$translator->addJsonPath($path);
		});
	}

	/**
	 * Register database migration paths.
	 */
	protected function loadMigrationsFrom(array|string $paths): void
	{
		$this->callAfterResolving('migrator', function ($migrator) use ($paths) {
			foreach ((array) $paths as $path) {
				$migrator->path($path);
			}
		});
	}

	/**
	 * Register a translation file namespace.
	 */
	protected function loadTranslationsFrom(string $path, string $namespace): void
	{
		$this->callAfterResolving('translator', function ($translator) use ($path, $namespace) {
			$translator->addNamespace($namespace, $path);
		});
	}

	/**
	 * Register the given view components with a custom prefix.
	 */
	protected function loadViewComponentsAs(string $prefix, array $components): void
	{
		$this->callAfterResolving(BladeCompiler::class, function ($blade) use ($prefix, $components) {
			foreach ($components as $alias => $component) {
				$blade->component($component, is_string($alias) ? $alias : null, $prefix);
			}
		});
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
	 * Get the paths for the provider and group.
	 */
	protected static function pathsForProviderAndGroup(string $provider, string $group): array
	{
		if (! empty(static::$publishes[$provider]) && ! empty(static::$publishGroups[$group])) {
			return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
		}

		return [];
	}

	/**
	 * Get the paths for the provider or group (or both).
	 */
	protected static function pathsForProviderOrGroup(string|null $provider, string|null $group): array|null
	{
		if ($provider && $group) {
			return static::pathsForProviderAndGroup($provider, $group);
		} elseif ($group && array_key_exists($group, static::$publishGroups)) {
			return static::$publishGroups[$group];
		} elseif ($provider && array_key_exists($provider, static::$publishes)) {
			return static::$publishes[$provider];
		} elseif ($group || $provider) {
			return [];
		}

		return null;
	}

	/**
	 * Get the paths to publish.
	 */
	public static function pathsToPublish(string|null $provider = null, string|null $group = null): array
	{
		$paths = static::pathsForProviderOrGroup($provider, $group);

		if (! is_null($paths)) {
			return $paths;
		}

		return collection(static::$publishes)
			->reduce(fn ($paths, $p) => array_merge($paths, $p), []);
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
	 * Get the migration paths available for publishing.
	 */
	public static function publishableMigrationPaths(): array
	{
		return static::$publishableMigrationPaths;
	}

	/**
	 * Get the groups available for publishing.
	 */
	public static function publishableGroups(): array
	{
		return array_keys(static::$publishGroups);
	}

	/**
	 * Get the service providers available for publishing.
	 */
	public static function publishableProviders(): array
	{
		return array_keys(static::$publishes);
	}

	/**
	 * Register paths to be published by the publish command.
	 */
	protected function publishes(array $paths, mixed $groups = null): void
	{
		$class = static::class;

		$this->ensurePublishArrayInitialized($class);

		static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

		foreach ((array) $groups as $group) {
			$this->addPublishGroup($group, $paths);
		}
	}

	/**
	 * Register migration paths to be published by the publish command.
	 */
	protected function publishesMigrations(array $paths, mixed $groups = null): void
	{
		$this->publishes($paths, $groups);

		if ($this->app->config->get('database.migrations.update_date_on_publish', false)) {
			static::$publishableMigrationPaths = array_unique(array_merge(
				static::$publishableMigrationPaths,
				array_keys($paths)
			));
		}
	}

	/**
	 * Register any application services.
	 */
	public function register(): void
	{
	}
}
