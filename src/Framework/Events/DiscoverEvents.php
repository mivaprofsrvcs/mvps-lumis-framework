<?php

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Support\Reflector;
use MVPS\Lumis\Framework\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverEvents
{
	/**
	 * A callback function to customize the guessing of
	 * class names from file paths.
	 *
	 * @var callable(SplFileInfo, string): string|null
	 */
	public static $guessClassNamesUsingCallback;

	/**
	 * Determine the class name from a given file and base path.
	 */
	protected static function classFromFile(SplFileInfo $file, string $basePath): string
	{
		if (static::$guessClassNamesUsingCallback) {
			return call_user_func(static::$guessClassNamesUsingCallback, $file, $basePath);
		}

		$class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

		return str_replace(
			[DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())) . '\\'],
			['\\', app()->getNamespace()],
			ucfirst(Str::replaceLast('.php', '', $class))
		);
	}

	/**
	 * Get all of the listeners and their corresponding events.
	 */
	protected static function getListenerEvents(iterable $listeners, string $basePath): array
	{
		$listenerEvents = [];

		foreach ($listeners as $listener) {
			try {
				$listener = new ReflectionClass(
					static::classFromFile($listener, $basePath)
				);
			} catch (ReflectionException) {
				continue;
			}

			if (! $listener->isInstantiable()) {
				continue;
			}

			foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				if (
					(! Str::is('handle*', $method->name) && ! Str::is('__invoke', $method->name)) ||
					! isset($method->getParameters()[0])
				) {
					continue;
				}

				$eventKey = $listener->name . '@' . $method->name;

				$listenerEvents[$eventKey] = Reflector::getParameterClassNames($method->getParameters()[0]);
			}
		}

		return array_filter($listenerEvents);
	}

	/**
	 * Set a custom callback to guess class names from file paths.
	 */
	public static function guessClassNamesUsing(callable $callback): void
	{
		static::$guessClassNamesUsingCallback = $callback;
	}

	/**
	 * Get all of the events and listeners by searching
	 * the given listener directory.
	 */
	public static function within(string $listenerPath, string $basePath): array
	{
		$listeners = collection(
			static::getListenerEvents(Finder::create()->files()->in($listenerPath), $basePath)
		);

		$discoveredEvents = [];

		foreach ($listeners as $listener => $events) {
			foreach ($events as $event) {
				if (! isset($discoveredEvents[$event])) {
					$discoveredEvents[$event] = [];
				}

				$discoveredEvents[$event][] = $listener;
			}
		}

		return $discoveredEvents;
	}
}
