<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use Exception;
use SplFileInfo;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Config\Repository;
use MVPS\Lumis\Framework\Contracts\Bootstrap\Bootstrapper;
use MVPS\Lumis\Framework\Contracts\Config\Repository as ConfigRepositoryContract;

class LoadConfiguration implements Bootstrapper
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$config = new Repository;

		// Iterate through all of the configuration files in the configuration
		// directory and load each one into the repository.
		$app->instance('config', $config);

		$this->loadConfigurationFiles($app, $config);

		date_default_timezone_set($config->get('app.timezone', 'UTC'));

		mb_internal_encoding('UTF-8');
	}

	/**
	 * Get all of the configuration files for the application.
	 */
	protected function getConfigurationFiles(Application $app): array
	{
		$files = [];
		$directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app->configPath()));

		foreach ($directoryIterator as $file) {
			if ($file->isDir() || $file->getExtension() !== 'php') {
				continue;
			}

			$directory = $this->getNestedDirectory($file, $app->configPath());

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
	 * Load the configuration items from all of the files.
	 *
	 * @throws \Exception
	 */
	protected function loadConfigurationFiles(Application $app, ConfigRepositoryContract $repository): void
	{
		$files = $this->getConfigurationFiles($app);

		if (! isset($files['app'])) {
			throw new Exception('Unable to load the "app" configuration file.');
		}

		foreach ($files as $key => $path) {
			$repository->set($key, require $path);
		}
	}
}
