<?php

namespace MVPS\Lumis\Framework\Filesystem;

use MVPS\Lumis\Framework\Providers\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
	/**
	 * Register the filesystem service provider.
	 */
	public function register(): void
	{
		$this->registerNativeFilesystem();
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
