<?php

namespace MVPS\Lumis\Framework\View\Traits;

use Closure;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Support\Str;

trait ManagesEvents
{
	/**
	 * Register a class based view composer.
	 */
	protected function addClassEvent(string $view, string $class, string $prefix): Closure
	{
		$name = $prefix . $view;

		// When registering a class-based view "composer", the class will be
		// resolved from the application IoC container and its compose method
		// will be called. This approach provides convenient and testable view
		// composers, promoting  better code organization and dependency management.
		$callback = $this->buildClassEventCallback($class, $prefix);

		$this->addEventListener($name, $callback);

		return $callback;
	}

	/**
	 * Add a listener to the event dispatcher.
	 */
	protected function addEventListener(string $name, Closure $callback): void
	{
		if (str_contains($name, '*')) {
			$callback = function (string $name, array $data) use ($callback) {
				return $callback($data[0]);
			};
		}

		$this->events->listen($name, $callback);
	}

	/**
	 * Add an event for a given view.
	 */
	protected function addViewEvent(
		string $view,
		Closure|string $callback,
		string $prefix = 'composing: '
	): Closure|null {
		$view = $this->normalizeName($view);

		if ($callback instanceof Closure) {
			$this->addEventListener($prefix . $view, $callback);

			return $callback;
		} elseif (is_string($callback)) {
			return $this->addClassEvent($view, $callback, $prefix);
		}
	}

	/**
	 * Build a class based container callback Closure.
	 */
	protected function buildClassEventCallback(string $class, string $prefix): Closure
	{
		[$class, $method] = $this->parseClassEvent($class, $prefix);

		// With the class and method names identified, we can create a Closure
		// that resolves the instance from the IoC container and invokes the
		// specified method on it. The Closure will receive the provided arguments
		// as the composer's data, ensuring the method is called with the correct
		// context and parameters.
		return function () use ($class, $method) {
			return $this->container->make($class)->{$method}(...func_get_args());
		};
	}

	/**
	 * Call the composer for a given view.
	 */
	public function callComposer(View $view): void
	{
		if ($this->events->hasListeners($event = 'composing: ' . $view->name())) {
			$this->events->dispatch($event, [$view]);
		}
	}

	/**
	 * Call the creator for a given view.
	 */
	public function callCreator(View $view): void
	{
		if ($this->events->hasListeners($event = 'creating: ' . $view->name())) {
			$this->events->dispatch($event, [$view]);
		}
	}

	/**
	 * Determine the class event method based on the given prefix.
	 */
	protected function classEventMethodForPrefix(string $prefix): string
	{
		return str_contains($prefix, 'composing') ? 'compose' : 'create';
	}

	/**
	 * Register a view composer event.
	 */
	public function composer(array|string $views, Closure|string $callback): array
	{
		$composers = [];

		foreach ((array) $views as $view) {
			$composers[] = $this->addViewEvent($view, $callback);
		}

		return $composers;
	}

	/**
	 * Register multiple view composers via an array.
	 */
	public function composers(array $composers): array
	{
		$registered = [];

		foreach ($composers as $callback => $views) {
			$registered = array_merge($registered, $this->composer($views, $callback));
		}

		return $registered;
	}

	/**
	 * Register a view creator event.
	 */
	public function creator(array|string $views, Closure|string $callback): array
	{
		$creators = [];

		foreach ((array) $views as $view) {
			$creators[] = $this->addViewEvent($view, $callback, 'creating: ');
		}

		return $creators;
	}

	/**
	 * Parse a class based composer name.
	 */
	protected function parseClassEvent(string $class, string $prefix): array
	{
		return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
	}
}
