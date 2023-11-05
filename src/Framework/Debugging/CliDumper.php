<?php

namespace MVPS\Lumis\Framework\Debugging;

use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

class CliDumper extends BaseCliDumper
{
	/**
	 * The base path of the application.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * Create a new cli dumper instance.
	 */
	public function __construct(string $basePath)
	{
		parent::__construct();

		$this->basePath = $basePath;
	}

	/**
	 * Register the cli dumper as the default application dumper.
	 */
	public static function register(string $basePath): void
	{
		$cloner = new VarCloner();

		$cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

		$dumper = new static($basePath);

		VarDumper::setHandler(fn ($value) => $dumper->dump($cloner->cloneVar($value)));
	}
}
