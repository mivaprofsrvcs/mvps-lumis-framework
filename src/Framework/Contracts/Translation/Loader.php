<?php

namespace MVPS\Lumis\Framework\Contracts\Translation;

interface Loader
{
	/**
	 * Add a new JSON path to the loader.
	 *
	 * @return void
	 */
	public function addJsonPath(string $path);

	/**
	 * Add a new namespace to the loader.
	 *
	 * @return void
	 */
	public function addNamespace(string $namespace, string $hint);

	/**
	 * Load the messages.
	 */
	public function load(string $group, string|null $namespace = null): array;

	/**
	 * Get the list of all the registered namespaces.
	 */
	public function namespaces(): array;
}
