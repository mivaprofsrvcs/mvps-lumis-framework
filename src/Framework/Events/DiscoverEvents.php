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

namespace MVPS\Lumis\Framework\Events;

use Illuminate\Support\Reflector;
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
