<?php

namespace MVPS\Lumis\Framework\View\Engines;

use Illuminate\View\Engines\PhpEngine as IlluminatePhpEngine;
use MVPS\Lumis\Framework\Contracts\View\Engine as EngineContract;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class PhpEngine extends IlluminatePhpEngine implements EngineContract
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
