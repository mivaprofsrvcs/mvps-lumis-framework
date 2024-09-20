<?php

namespace MVPS\Lumis\Framework\Console;

use Illuminate\Console\Signals;
use MVPS\Lumis\Framework\Console\Commands\AboutCommand;
use MVPS\Lumis\Framework\Console\Commands\Cache\CacheTableCommand;
use MVPS\Lumis\Framework\Console\Commands\ComponentMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ConfigShowCommand;
use MVPS\Lumis\Framework\Console\Commands\ConsoleMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ControllerMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\DbCommand;
use MVPS\Lumis\Framework\Console\Commands\DbMonitorCommand;
use MVPS\Lumis\Framework\Console\Commands\DbShowCommand;
use MVPS\Lumis\Framework\Console\Commands\DbTableCommand;
use MVPS\Lumis\Framework\Console\Commands\EnvironmentCommand;
use MVPS\Lumis\Framework\Console\Commands\EventGenerateCommand;
use MVPS\Lumis\Framework\Console\Commands\EventListCommand;
use MVPS\Lumis\Framework\Console\Commands\EventMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ExceptionMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\FactoryMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ListenerMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\MiddlewareMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ModelMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ModelPruneCommand;
use MVPS\Lumis\Framework\Console\Commands\ProviderMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\RouteListCommand;
use MVPS\Lumis\Framework\Console\Commands\RuleMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\SchemaDumpCommand;
use MVPS\Lumis\Framework\Console\Commands\Seeds\SeedCommand;
use MVPS\Lumis\Framework\Console\Commands\Seeds\SeederMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ServeCommand;
use MVPS\Lumis\Framework\Console\Commands\TaskMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\ViewMakeCommand;
use MVPS\Lumis\Framework\Console\Commands\WipeCommand;
use MVPS\Lumis\Framework\Contracts\Support\DeferrableProvider;
use MVPS\Lumis\Framework\Providers\ServiceProvider;

class LumisServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * The commands to be registered.
	 *
	 * @var array
	 */
	protected array $commands = [
		'About' => AboutCommand::class,
		// 'CacheClear' => CacheClearCommand::class,
		// 'CacheForget' => CacheForgetCommand::class,
		// 'ClearCompiled' => ClearCompiledCommand::class,
		// 'ConfigCache' => ConfigCacheCommand::class,
		// 'ConfigClear' => ConfigClearCommand::class,
		'ConfigShow' => ConfigShowCommand::class,
		'Db' => DbCommand::class,
		'DbMonitor' => DbMonitorCommand::class,
		'DbShow' => DbShowCommand::class,
		'DbTable' => DbTableCommand::class,
		'DbWipe' => WipeCommand::class,
		'Environment' => EnvironmentCommand::class,
		// 'EnvironmentDecrypt' => EnvironmentDecryptCommand::class,
		// 'EnvironmentEncrypt' => EnvironmentEncryptCommand::class,
		// 'EventCache' => EventCacheCommand::class,
		// 'EventClear' => EventClearCommand::class,
		'EventList' => EventListCommand::class,
		// 'KeyGenerate' => KeyGenerateCommand::class,
		'ModelPrune' => ModelPruneCommand::class,
		// 'Optimize' => OptimizeCommand::class,
		// 'OptimizeClear' => OptimizeClearCommand::class,
		// 'RouteCache' => RouteCacheCommand::class,
		// 'RouteClear' => RouteClearCommand::class,
		'RouteList' => RouteListCommand::class,
		'Seed' => SeedCommand::class,
		'SchemaDump' => SchemaDumpCommand::class,
		// 'ViewCache' => ViewCacheCommand::class,
		// 'ViewClear' => ViewClearCommand::class,
	];

	/**
	 * The dev commands to be registered.
	 *
	 * @var array
	 */
	protected array $devCommands = [
		'CacheTable' => CacheTableCommand::class,
		'ComponentMake' => ComponentMakeCommand::class,
		// 'ConfigPublish' => ConfigPublishCommand::class,
		'ConsoleMake' => ConsoleMakeCommand::class,
		'ControllerMake' => ControllerMakeCommand::class,
		'EventGenerate' => EventGenerateCommand::class,
		'EventMake' => EventMakeCommand::class,
		'ExceptionMake' => ExceptionMakeCommand::class,
		'FactoryMake' => FactoryMakeCommand::class,
		'ListenerMake' => ListenerMakeCommand::class,
		'ModelMake' => ModelMakeCommand::class,
		'MiddlewareMake' => MiddlewareMakeCommand::class,
		'ProviderMake' => ProviderMakeCommand::class,
		'RuleMake' => RuleMakeCommand::class,
		'SeederMake' => SeederMakeCommand::class,
		'Serve' => ServeCommand::class,
		'TaskMake' => TaskMakeCommand::class,
		'ViewMake' => ViewMakeCommand::class,
	];

	/**
	 * Get the Lumis command services.
	 */
	public function provides(): array
	{
		return array_merge(array_values($this->commands), array_values($this->devCommands));
	}

	/**
	 * Register the Lumis console command service provider.
	 */
	public function register(): void
	{
		$this->registerCommands(array_merge(
			$this->commands,
			$this->devCommands
		));

		Signals::resolveAvailabilityUsing(function () {
			return $this->app->runningInConsole() && extension_loaded('pcntl');
		});
	}

	/**
	 * Register the given commands.
	 */
	protected function registerCommands(array $commands): void
	{
		foreach ($commands as $commandName => $command) {
			$method = 'register' . $commandName . 'Command';

			if (method_exists($this, $method)) {
				$this->{$method}();
			} else {
				$this->app->singleton($command);
			}
		}

		$this->commands(array_values($commands));
	}

	/**
	 * Register the cache table make command.
	 */
	protected function registerCacheTableCommand(): void
	{
		$this->app->singleton(CacheTableCommand::class, function ($app) {
			return new CacheTableCommand($app['files']);
		});
	}

	/**
	 * Register the component make command.
	 */
	protected function registerComponentMakeCommand(): void
	{
		$this->app->singleton(ComponentMakeCommand::class, function ($app) {
			return new ComponentMakeCommand($app['files']);
		});
	}

	/**
	 * Register the console make command.
	 */
	protected function registerConsoleMakeCommand(): void
	{
		$this->app->singleton(ConsoleMakeCommand::class, function ($app) {
			return new ConsoleMakeCommand($app['files']);
		});
	}

	/**
	 * Register the controller make command.
	 */
	protected function registerControllerMakeCommand(): void
	{
		$this->app->singleton(ControllerMakeCommand::class, function ($app) {
			return new ControllerMakeCommand($app['files']);
		});
	}

	/**
	 * Register the event make command.
	 */
	protected function registerEventMakeCommand(): void
	{
		$this->app->singleton(EventMakeCommand::class, function ($app) {
			return new EventMakeCommand($app['files']);
		});
	}

	/**
	 * Register the exception make command.
	 */
	protected function registerExceptionMakeCommand(): void
	{
		$this->app->singleton(ExceptionMakeCommand::class, function ($app) {
			return new ExceptionMakeCommand($app['files']);
		});
	}

	/**
	 * Register the listener make command.
	 */
	protected function registerListenerMakeCommand(): void
	{
		$this->app->singleton(ListenerMakeCommand::class, function ($app) {
			return new ListenerMakeCommand($app['files']);
		});
	}

	/**
	* Register the middleware make command.
	*/
	protected function registerMiddlewareMakeCommand(): void
	{
		$this->app->singleton(MiddlewareMakeCommand::class, function ($app) {
			return new MiddlewareMakeCommand($app['files']);
		});
	}

	/**
	 * Register the model make command.
	 */
	protected function registerModelMakeCommand(): void
	{
		$this->app->singleton(ModelMakeCommand::class, function ($app) {
			return new ModelMakeCommand($app['files']);
		});
	}

	/**
	 * Register the provider make command.
	 */
	protected function registerProviderMakeCommand(): void
	{
		$this->app->singleton(ProviderMakeCommand::class, function ($app) {
			return new ProviderMakeCommand($app['files']);
		});
	}

	/**
	 * Register the route list command.
	 */
	protected function registerRouteListCommand(): void
	{
		$this->app->singleton(RouteListCommand::class, function ($app) {
			return new RouteListCommand($app['router']);
		});
	}

	/**
	 * Register the rule make command.
	 */
	protected function registerRuleMakeCommand()
	{
		$this->app->singleton(RuleMakeCommand::class, function ($app) {
			return new RuleMakeCommand($app['files']);
		});
	}

	/**
	 * Register the task make command.
	 */
	protected function registerTaskMakeCommand(): void
	{
		$this->app->singleton(TaskMakeCommand::class, function ($app) {
			return new TaskMakeCommand($app['files']);
		});
	}
}
