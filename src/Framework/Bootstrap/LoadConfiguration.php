<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use Exception;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Configuration\Repository;
use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;
use MVPS\Lumis\Framework\Contracts\Framework\Application as ApplicationContract;
use MVPS\Lumis\Framework\Contracts\Configuration\Repository as ConfigRepositoryContract;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class LoadConfiguration implements Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(ApplicationContract $app): void
	{
		$items = [];

		// Check if a cache configuration file exists. If it does, load the
		// configuration items from that file for quick access. If not,
		// iterate through all configuration files and load them individually.
		$cached = $app->getCachedConfigPath();

		if (file_exists($cached)) {
			$items = require $cached;

			$app->instance('config_loaded_from_cache', $loadedFromCache = true);
		}

		// Iterate through all configuration files in the configuration directory
		// and load each one into the repository. This process makes all options
		// available to the developer for use throughout the application.
		$config = new Repository($items);

		$app->instance('config', $config);

		if (! isset($loadedFromCache)) {
			$this->loadConfigurationFiles($app, $config);
		}

		// Set the application's environment based on the loaded configuration
		// values. If the "--env" switch is not present (e.g., in a web context),
		// we will use a callback to determine the environment.
		$app->detectEnvironment(fn () => $config->get('app.env', 'production'));

		date_default_timezone_set($config->get('app.timezone', 'UTC'));

		mb_internal_encoding('UTF-8');
	}

	/**
	 * Get the base configuration files.
	 */
	protected function getBaseConfiguration(): array
	{
		$config = [];

		foreach (Finder::create()->files()->name('*.php')->in(Application::FRAMEWORK_CONFIG_PATH) as $file) {
			$config[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
		}

		return $config;
	}

	/**
	 * Get all of the configuration files for the application.
	 */
	protected function getConfigurationFiles(ApplicationContract $app): array
	{
		$files = [];

		$configPath = realpath($app->configPath());

		if (! $configPath) {
			return [];
		}

		foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
			$directory = $this->getNestedDirectory($file, $configPath);

			$files[$directory . basename($file->getRealPath(), '.php')] = $file->getRealPath();
		}

		ksort($files, SORT_NATURAL);

		return $files;
	}

	/**
	 * Get the configuration file nesting path.
	 */
	protected function getNestedDirectory(SplFileInfo $file, string $configPath): string
	{
		$directory = $file->getPath();
		$nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR);

		if ($nested) {
			$nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested) . '.';
		}

		return $nested;
	}

	/**
	 * Load the given configuration file.
	 */
	protected function loadConfigurationFile(
		ConfigRepositoryContract $repository,
		string $name,
		string $path,
		array $base
	): array {
		$config = require $path;

		if (isset($base[$name])) {
			$config = array_merge($base[$name], $config);

			foreach ($this->mergeableOptions($name) as $option) {
				if (isset($config[$option])) {
					$config[$option] = array_merge($base[$name][$option], $config[$option]);
				}
			}

			unset($base[$name]);
		}

		$repository->set($name, $config);

		return $base;
	}

	/**
	 * Load the configuration items from all of the files.
	 *
	 * @throws \Exception
	 */
	protected function loadConfigurationFiles(ApplicationContract $app, ConfigRepositoryContract $repository): void
	{
		$files = $this->getConfigurationFiles($app);

		$shouldMerge = method_exists($app, 'shouldMergeFrameworkConfiguration')
			? $app->shouldMergeFrameworkConfiguration()
			: true;

		$base = $shouldMerge
			? $this->getBaseConfiguration()
			: [];

		foreach (array_diff(array_keys($base), array_keys($files)) as $name => $config) {
			$repository->set($name, $config);
		}

		foreach ($files as $name => $path) {
			$base = $this->loadConfigurationFile($repository, $name, $path, $base);
		}

		foreach ($base as $name => $config) {
			$repository->set($name, $config);
		}
	}

	/**
	 * Get the options within the configuration file that should be merged again.
	 */
	protected function mergeableOptions(string $name): array
	{
		return [
			// 'auth' => ['guards', 'providers', 'passwords'],
			// 'broadcasting' => ['connections'],
			'cache' => ['stores'],
			'database' => ['connections'],
			// 'filesystems' => ['disks'],
			// 'logging' => ['channels'],
			// 'mail' => ['mailers'],
			// 'queue' => ['connections'],
		][$name] ?? [];
	}
}
