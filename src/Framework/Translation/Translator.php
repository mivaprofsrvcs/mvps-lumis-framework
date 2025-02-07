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

namespace MVPS\Lumis\Framework\Translation;

use Closure;
use Countable;
use Illuminate\Support\NamespacedItemResolver;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\ReflectsClosures;
use MVPS\Lumis\Framework\Contracts\Translation\Loader;
use MVPS\Lumis\Framework\Contracts\Translation\Translator as TranslatorContract;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;

class Translator extends NamespacedItemResolver implements TranslatorContract
{
	use Macroable;
	use ReflectsClosures;

	/**
	 * Indicates whether missing translation keys should be handled.
	 *
	 * @var bool
	 */
	protected bool $handleMissingTranslationKeys = true;

	/**
	 * The array of loaded translation groups.
	 *
	 * @var array
	 */
	protected array $loaded = [];

	/**
	 * The translation loader implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Translation\Loader
	 */
	protected Loader $loader;

	/**
	 * The callback that is responsible for handling missing translation keys.
	 *
	 * @var callable|null
	 */
	protected $missingTranslationKeyCallback = null;

	/**
	 * The message selector instance.
	 *
	 * @var \MVPS\Lumis\Framework\Translation\MessageSelector|null
	 */
	protected MessageSelector|null $selector = null;

	/**
	 * The custom rendering callbacks for stringable objects.
	 *
	 * @var array
	 */
	protected array $stringableHandlers = [];

	/**
	 * Create a new translator instance.
	 */
	public function __construct(Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Add a new JSON path to the loader.
	 */
	public function addJsonPath(string $path): void
	{
		$this->loader->addJsonPath($path);
	}

	/**
	 * Add translation lines.
	 */
	public function addLines(array $lines, string $namespace = '*'): void
	{
		foreach ($lines as $key => $value) {
			[$group, $item] = explode('.', $key, 2);

			Arr::set($this->loaded, "$namespace.$group.$item", $value);
		}
	}

	/**
	 * Add a new namespace to the loader.
	 */
	public function addNamespace(string $namespace, string $hint): void
	{
		$this->loader->addNamespace($namespace, $hint);
	}

	/**
	 * {@inheritdoc}
	 */
	public function choice(string $key, Countable|int|float|array $number, array $replace = []): string
	{
		$line = $this->get($key, $replace);

		// If the given "number" is an array or implements the Countable
		// interface, we will count its elements automatically. This spares
		// developers from manually counting arrays before passing them,
		// simplifying the syntax and improving readability.
		if (is_countable($number)) {
			$number = count($number);
		}

		$replace['count'] = $number;

		return $this->makeReplacements(
			$this->getSelector()->choose($line, $number),
			$replace
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $key, array $replace = []): string|array
	{
		// For JSON translations, there’s only a single file. We'll load that
		// file and check the array for the key. Since JSON translations are
		// flat, no deep searching is required.
		$this->load('*', '*');

		$line = $this->loaded['*']['*'][$key] ?? null;

		// If the JSON key can't be found, we'll fall back to using the standard
		// translation files. This allows developers to consistently use helpers
		// like __ without having to worry about choosing between trans and __
		// in views.
		if (! isset($line)) {
			[$namespace, $group, $item] = $this->parseKey($key);

			$line = $this->getLine($namespace, $group, $item, $replace);

			if (! is_null($line)) {
				return $line;
			}

			$key = $this->handleMissingTranslationKey($key, $replace);
		}

		// If the requested translation line doesn't exist, we’ll return the key
		// itself. This makes it easy to identify missing keys in the UI, while
		// providing a fallback if the translation is missing.
		return $this->makeReplacements($line ?: $key, $replace);
	}

	/**
	 * Retrieve a translation line out the loaded array.
	 */
	protected function getLine(string $namespace, string $group, string $item, array $replace): string|array|null
	{
		$this->load($namespace, $group);

		$line = Arr::get($this->loaded[$namespace][$group], $item);

		if (is_string($line)) {
			return $this->makeReplacements($line, $replace);
		} elseif (is_array($line) && count($line) > 0) {
			array_walk_recursive($line, function (&$value, $key) use ($replace) {
				$value = $this->makeReplacements($value, $replace);
			});

			return $line;
		}

		return null;
	}

	/**
	 * Get the translation line loader implementation.
	 */
	public function getLoader(): Loader
	{
		return $this->loader;
	}

	/**
	 * Get the message selector instance.
	 */
	public function getSelector(): MessageSelector
	{
		if (is_null($this->selector)) {
			$this->selector = new MessageSelector;
		}

		return $this->selector;
	}

	/**
	 * Register a callback that is responsible for handling missing
	 * translation keys.
	 */
	public function handleMissingKeysUsing(callable|null $callback): static
	{
		$this->missingTranslationKeyCallback = $callback;

		return $this;
	}

	/**
	 * Handle a missing translation key.
	 */
	protected function handleMissingTranslationKey(string $key, array $replace): string
	{
		if (! $this->handleMissingTranslationKeys || is_null($this->missingTranslationKeyCallback)) {
			return $key;
		}

		// Prevent infinite loops
		$this->handleMissingTranslationKeys = false;

		$key = call_user_func($this->missingTranslationKeyCallback, $key, $replace) ?? $key;

		$this->handleMissingTranslationKeys = true;

		return $key;
	}

	/**
	 * Determine if a translation exists.
	 */
	public function has(string $key): bool
	{
		// Temporarily disable the handling of missing translation keys while
		// performing this existence check. Once the check is complete, we'll
		// restore the original missing translation handling behavior.
		$handleMissingTranslationKeys = $this->handleMissingTranslationKeys;

		$this->handleMissingTranslationKeys = false;

		$line = $this->get($key, []);

		$this->handleMissingTranslationKeys = $handleMissingTranslationKeys;

		// For JSON translations, the loaded files should already contain the
		// correct line. Otherwise, we assume a standard translation file is
		// being used and check if the returned translation is different from
		// the original key.
		if (! is_null($this->loaded['*']['*'][$key] ?? null)) {
			return true;
		}

		return $line !== $key;
	}

	/**
	 * Determine if the given group has been loaded.
	 */
	protected function isLoaded(string $namespace, string $group): bool
	{
		return isset($this->loaded[$namespace][$group]);
	}

	/**
	 * Load the given group.
	 */
	public function load(string $namespace, string $group): void
	{
		if ($this->isLoaded($namespace, $group)) {
			return;
		}

		$lines = $this->loader->load($group, $namespace);

		$this->loaded[$namespace][$group] = $lines;
	}

	/**
	 * Make the place-holder replacements on a line.
	 */
	protected function makeReplacements(string $line, array $replace): string
	{
		if (empty($replace)) {
			return $line;
		}

		$shouldReplace = [];

		foreach ($replace as $key => $value) {
			if ($value instanceof Closure) {
				$line = preg_replace_callback(
					'/<' . $key . '>(.*?)<\/' . $key . '>/',
					fn ($args) => $value($args[1]),
					$line
				);

				continue;
			}

			if (is_object($value) && isset($this->stringableHandlers[get_class($value)])) {
				$value = call_user_func($this->stringableHandlers[get_class($value)], $value);
			}

			$shouldReplace[':' . Str::ucfirst($key)] = Str::ucfirst($value ?? '');
			$shouldReplace[':' . Str::upper($key)] = Str::upper($value ?? '');
			$shouldReplace[':' . $key] = $value;
		}

		return strtr($line, $shouldReplace);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function parseKey($key)
	{
		$segments = parent::parseKey($key);

		if (is_null($segments[0])) {
			$segments[0] = '*';
		}

		return $segments;
	}

	/**
	 * Set the loaded translation groups.
	 */
	public function setLoaded(array $loaded): void
	{
		$this->loaded = $loaded;
	}

	/**
	 * Set the message selector instance.
	 */
	public function setSelector(MessageSelector $selector): void
	{
		$this->selector = $selector;
	}

	/**
	 * Add a handler to be executed in order to format a given class to a
	 * string during translation replacements.
	 */
	public function stringable(callable|string $class, callable|null $handler = null): void
	{
		if ($class instanceof Closure) {
			[$class, $handler] = [
				$this->firstClosureParameterType($class),
				$class,
			];
		}

		$this->stringableHandlers[$class] = $handler;
	}
}
