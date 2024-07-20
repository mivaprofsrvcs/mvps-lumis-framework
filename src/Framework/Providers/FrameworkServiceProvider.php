<?php

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Debugging\CliDumper;
use MVPS\Lumis\Framework\Debugging\HtmlDumper;
use Symfony\Component\VarDumper\Caster\StubCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

class FrameworkServiceProvider extends AggregateServiceProvider
{
	/**
	 * Register the framework service provider.
	 *
	 * @return void
	 */
	public function register(): void
	{
		parent::register();

		$this->registerDumper();
		// $this->registerExceptionTracking();
		// $this->registerExceptionRenderer();
	}

	/**
	 * Register a var dumper (with source) to debug variables.
	 */
	public function registerDumper(): void
	{
		AbstractCloner::$defaultCasters[Container::class] = [StubCaster::class, 'cutInternals'];
		AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];

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
