<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'trans:publish')]
class TransPublishCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Publish all translation files that are available for customization';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'trans:publish
		{--existing : Publish and overwrite only the files that have already been published}
		{--force : Overwrite any existing files}';

	/**
	 * Execute the publish translations command.
	 */
	public function handle(): void
	{
		$transPath = $this->lumis->basePath('translations');

		if (! is_dir($transPath)) {
			(new Filesystem)->makeDirectory($transPath, recursive: true);
		}

		$basePath = Application::FRAMEWORK_PATH;

		$stubs = [
			// realpath($basePath . '/Translation/translations/auth.php') => 'auth.php',
			// realpath($basePath . '/Translation/translations/pagination.php') => 'pagination.php',
			// realpath($basePath . '/Translation/translations/passwords.php') => 'passwords.php',
			realpath($basePath . '/Translation/translations/validation.php') => 'validation.php',
		];

		foreach ($stubs as $from => $to) {
			$to = $transPath . DIRECTORY_SEPARATOR . ltrim($to, DIRECTORY_SEPARATOR);

			if (
				(! $this->option('existing') && (! file_exists($to) || $this->option('force'))) ||
				($this->option('existing') && file_exists($to))
			) {
				file_put_contents($to, file_get_contents($from));
			}
		}

		$this->components->info('Translation files published successfully.');
	}
}
