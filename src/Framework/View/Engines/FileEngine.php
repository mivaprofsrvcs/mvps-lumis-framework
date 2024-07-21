<?php

namespace MVPS\Lumis\Framework\View\Engines;

use Illuminate\View\Engines\FileEngine as IlluminateFileEngine;
use MVPS\Lumis\Framework\Contracts\View\Engine as EngineContract;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class FileEngine extends IlluminateFileEngine implements EngineContract
{
	/**
	 * {@inheritdoc}
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new php engine instance.
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}
}
