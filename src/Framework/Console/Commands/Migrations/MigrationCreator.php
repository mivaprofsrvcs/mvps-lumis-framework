<?php

namespace MVPS\Lumis\Framework\Console\Commands\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class MigrationCreator extends IlluminateMigrationCreator
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new migration creator instance.
	 */
	public function __construct(Filesystem $files, string $customStubPath = '')
	{
		parent::__construct($files, $customStubPath);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function stubPath(): string
	{
		return __DIR__ . '/stubs';
	}
}
