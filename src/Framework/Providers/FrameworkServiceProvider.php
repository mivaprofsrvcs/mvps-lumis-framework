<?php

namespace MVPS\Lumis\Framework\Providers;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Framework\Application as ApplicationContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactoryContract;
use MVPS\Lumis\Framework\Debugging\CliDumper;
use MVPS\Lumis\Framework\Debugging\HtmlDumper;
use MVPS\Lumis\Framework\Exceptions\Renderer\Listener;
use MVPS\Lumis\Framework\Exceptions\Renderer\Mappers\BladeMapper;
use MVPS\Lumis\Framework\Exceptions\Renderer\Renderer;
use MVPS\Lumis\Framework\Http\Client\Factory as HttpFactory;
use MVPS\Lumis\Framework\Log\Events\MessageLogged;
use MVPS\Lumis\Framework\Testing\LoggedExceptionCollection;
use MVPS\Lumis\Framework\View\Factory as ViewFactory;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\VarDumper\Caster\StubCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

class FrameworkServiceProvider extends AggregateServiceProvider
{
	/**
	 * The singletons to register into the container.
	 *
	 * @var array
	 */
	public array $singletons = [
		HttpFactory::class => HttpFactory::class,
	];

	/**
	 * Boot the framework service provider.
	 */
	public function boot(): void
	{
		if ($this->app->runningInConsole()) {
			$this->publishes(
				[__DIR__ . '/../Exceptions/views' => $this->app->resourcePath('views/errors/')],
				'lumis-errors'
			);
		}

		if ($this->app->hasDebugModeEnabled()) {
			$this->app->make(Listener::class)
				->registerListeners($this->app->make(Dispatcher::class));
		}
	}

	/**
	 * Register the framework service provider.
	 */
	public function register(): void
	{
		parent::register();

		$this->registerDumper();
		$this->registerExceptionTracking();
		$this->registerExceptionRenderer();
	}

	/**
	 * Register a var dumper (with source) to debug variables.
	 */
	public function registerDumper(): void
	{
		AbstractCloner::$defaultCasters[Container::class] ??= [StubCaster::class, 'cutInternals'];
		AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];
		AbstractCloner::$defaultCasters[ViewFactory::class] ??= [StubCaster::class, 'cutInternals'];

		$basePath = $this->app->basePath();

		$compiledViewPath = $this->app['config']->get('view.compiled');

		$format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

		match (true) {
			$format === 'html' => HtmlDumper::register($basePath, $compiledViewPath),
			$format === 'cli' => CliDumper::register($basePath, $compiledViewPath),
			$format === 'server' => null,
			$format && 'tcp' === parse_url($format, PHP_URL_SCHEME) => null,
			default => in_array(PHP_SAPI, ['cli', 'phpdbg'])
				? CliDumper::register($basePath, $compiledViewPath)
				: HtmlDumper::register($basePath, $compiledViewPath),
		};
	}

	/**
	 * Register the exceptions renderer.
	 */
	protected function registerExceptionRenderer(): void
	{
		if (! $this->app->hasDebugModeEnabled()) {
			return;
		}

		$this->loadViewsFrom(
			Application::FRAMEWORK_RESOURCES_PATH . '/exceptions/renderer',
			'lumis-exceptions-renderer'
		);

		$this->app->singleton(Renderer::class, function (ApplicationContract $app) {
			$errorRenderer = new HtmlErrorRenderer($app['config']->get('app.debug'));

			return new Renderer(
				$app->make(ViewFactoryContract::class),
				$app->make(Listener::class),
				$errorRenderer,
				$app->make(BladeMapper::class),
				$app->basePath()
			);
		});
	}

	/**
	 * Register an event listener to track logged exceptions.
	 */
	protected function registerExceptionTracking(): void
	{
		if (! $this->app->runningUnitTests()) {
			return;
		}

		$this->app->instance(LoggedExceptionCollection::class, new LoggedExceptionCollection);

		$this->app->make('events')->listen(MessageLogged::class, function ($event) {
			if (isset($event->context['exception'])) {
				$this->app->make(LoggedExceptionCollection::class)
					->push($event->context['exception']);
			}
		});
	}
}
