<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\FileViewFinder as IlluminateFileViewFinder;
use MVPS\Lumis\Framework\Contracts\View\ViewFinder;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class FileViewFinder extends IlluminateFileViewFinder implements ViewFinder
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new file view finder instance.
	 */
	public function __construct(Filesystem $files, array $paths, array|null $extensions = null)
	{
		parent::__construct($files, $paths, $extensions);
	}
}
