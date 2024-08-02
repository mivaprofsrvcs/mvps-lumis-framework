<?php

namespace MVPS\Lumis\Framework\Filesystem;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
	/**
	 * Get the default file driver.
	 */
	protected function getDefaultDriver(): string
	{
		return $this->app['config']['filesystems.default'];
	}

	/**
	 * Register the filesystem service provider.
	 */
	public function register(): void
	{
		$this->registerNativeFilesystem();

		$this->registerDrivers();
	}

	/**
	 * Register the driver based filesystem.
	 */
	protected function registerDrivers(): void
	{
		$this->registerManager();

		$this->app->singleton('filesystem.disk', function ($app) {
			return $app['filesystem']->disk($this->getDefaultDriver());
		});
	}

	/**
	 * Register the filesystem manager.
	 */
	protected function registerManager(): void
	{
		$this->app->singleton('filesystem', function ($app) {
			return new FilesystemManager($app);
		});
	}

	/**
	 * Register the native filesystem implementation.
	 */
	protected function registerNativeFilesystem(): void
	{
		$this->app->singleton('files', function () {
			return new Filesystem;
		});
	}
}
