<?php

namespace MVPS\Lumis\Framework\Debugging;

use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Providers\ServiceProvider;
use Symfony\Component\VarDumper\Caster\StubCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

class DumperServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->registerDumper();
	}

	/**
	 * Register a var dumper (with source) to debug variables.
	 */
	public function registerDumper(): void
	{
		AbstractCloner::$defaultCasters[Container::class] = [StubCaster::class, 'cutInternals'];

		$basePath = $this->app->basePath();

		$format = $_SERVER['VAR_DUMPER_FORMAT'] ?? '';

		match (true) {
			$format === 'html' => HtmlDumper::register($basePath),
			$format === 'cli' => CliDumper::register($basePath),
			$format === 'server' => null,
			$format && 'tcp' === parse_url($format, PHP_URL_SCHEME) => null,
			default => in_array(PHP_SAPI, ['cli', 'phpdbg'])
				? CliDumper::register($basePath)
				: HtmlDumper::register($basePath),
		};
	}
}
