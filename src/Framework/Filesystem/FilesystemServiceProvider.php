<?php

namespace MVPS\Lumis\Framework\Filesystem;

use Illuminate\Contracts\Foundation\CachesRoutes;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the filesystem.
	 */
	public function boot(): void
	{
		$this->serveFiles();
	}

	/**
	 * Get the default file driver.
	 */
	protected function getDefaultDriver(): string
	{
		return $this->app['config']['filesystems.default'] ?? '';
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

	/**
	 * Register protected file serving.
	 */
	protected function serveFiles(): void
	{
		if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
			return;
		}

		foreach ($this->app['config']['filesystems.disks'] ?? [] as $disk => $config) {
			if (! $this->shouldServeFiles($config)) {
				continue;
			}

			$this->app->booted(function ($app) use ($disk, $config) {
				$uri = isset($config['url'])
					? rtrim(parse_url($config['url'])['path'], '/')
					: '/storage';

				$isProduction = $app->isProduction();

				app('router')
					->get(
						$uri . '/{path}',
						function (Request $request, string $path) use ($disk, $config, $isProduction) {
							return (new ServeFile($disk, $config, $isProduction))($request, $path);
						}
					)
					->where('path', '.*')
					->name('storage.' . $disk);
			});
		}
	}

	/**
	 * Determine if the disk is should serve files.
	 */
	protected function shouldServeFiles(array $config): bool
	{
		return $config['driver'] === 'local' && ($config['serve'] ?? false);
	}
}
