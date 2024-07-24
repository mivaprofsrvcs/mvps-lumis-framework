<?php

namespace MVPS\Lumis\Framework\View\Compilers;

use Illuminate\View\Compilers\Compiler as IlluminateCompiler;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

abstract class Compiler extends IlluminateCompiler
{
	/**
	 * Create a new compiler instance.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		Filesystem $files,
		$cachePath,
		$basePath = '',
		$shouldCache = true,
		$compiledExtension = 'php'
	) {
		parent::__construct($files, $cachePath, $basePath, $shouldCache, $compiledExtension);
	}
}
