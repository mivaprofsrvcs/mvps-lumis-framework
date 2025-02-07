<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\View;

use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\View\ViewFinder;
use MVPS\Lumis\Framework\Providers\ServiceProvider;
use MVPS\Lumis\Framework\View\Compilers\BladeCompiler;
use MVPS\Lumis\Framework\View\Engines\CompilerEngine;
use MVPS\Lumis\Framework\View\Engines\EngineResolver;
use MVPS\Lumis\Framework\View\Engines\FileEngine;
use MVPS\Lumis\Framework\View\Engines\PhpEngine;

class ViewServiceProvider extends ServiceProvider
{
	/**
	 * Create a new Factory Instance.
	 */
	protected function createFactory(EngineResolver $resolver, ViewFinder $finder, Dispatcher $events): Factory
	{
		return new Factory($resolver, $finder, $events);
	}

	/**
	 * Register the view service provider.
	 */
	public function register(): void
	{
		$this->registerFactory();
		$this->registerViewFinder();
		$this->registerBladeCompiler();
		$this->registerEngineResolver();
	}

	/**
	 * Register the Blade compiler implementation.
	 */
	public function registerBladeCompiler(): void
	{
		$this->app->singleton('blade.compiler', function ($app) {
			return tap(new BladeCompiler(
				$app['files'],
				$app['config']['view.compiled'],
				$app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
				$app['config']->get('view.cache', true),
				$app['config']->get('view.compiled_extension', 'php'),
			), function ($blade) {
				$blade->component('dynamic-component', DynamicComponent::class);
			});
		});
	}

	/**
	 * Register the Blade engine implementation.
	 */
	public function registerBladeEngine(EngineResolver $resolver): void
	{
		$resolver->register('blade', function () {
			$app = Container::getInstance();

			$compiler = new CompilerEngine($app->make('blade.compiler'), $app->make('files'));

			return $compiler;
		});
	}

	/**
	 * Register the engine resolver instance.
	 */
	public function registerEngineResolver(): void
	{
		$this->app->singleton('view.engine.resolver', function () {
			$resolver = new EngineResolver;

			// Next, we register various view engines with the resolver.
			// This setup enables the environment to determine the correct engine
			// for each view based on its file extension. We will invoke specific
			// methods to register each view engine accordingly.
			foreach (['file', 'php', 'blade'] as $engine) {
				$this->{'register' . ucfirst($engine) . 'Engine'}($resolver);
			}

			return $resolver;
		});
	}

	/**
	 * Register the view environment.
	 */
	public function registerFactory(): void
	{
		$this->app->singleton('view', function ($app) {
			// Retrieve the engine resolver instance to be used by the environment.
			// The resolver provides the necessary engine implementations, such
			// as the plain PHP engine or the Blade engine, for the environment's
			// rendering tasks.
			$resolver = $app['view.engine.resolver'];

			$finder = $app['view.finder'];

			$factory = $this->createFactory($resolver, $finder, $app['events']);

			// Set the container instance on the view environment. This enables view
			// composers to be classes registered within the container, providing
			// developers with testable and flexible composer implementations.
			$factory->setContainer($app);

			$factory->share('app', $app);

			return $factory;
		});
	}

	/**
	 * Register the file engine implementation.
	 */
	public function registerFileEngine(EngineResolver $resolver): void
	{
		$resolver->register('file', function () {
			return new FileEngine(Container::getInstance()->make('files'));
		});
	}

	/**
	 * Register the PHP engine implementation.
	 */
	public function registerPhpEngine(EngineResolver $resolver): void
	{
		$resolver->register('php', function () {
			return new PhpEngine(Container::getInstance()->make('files'));
		});
	}

	/**
	 * Register the view finder implementation.
	 */
	public function registerViewFinder(): void
	{
		$this->app->bind('view.finder', function ($app) {
			return new FileViewFinder($app['files'], $app['config']['view.paths']);
		});
	}
}
