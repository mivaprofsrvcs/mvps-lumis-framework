<?php

namespace MVPS\Lumis\Framework\Translation;

use MVPS\Lumis\Framework\Contracts\Translation\Loader;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use RuntimeException;

class FileLoader implements Loader
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected Filesystem $files;

	/**
	 * All of the namespace hints.
	 *
	 * @var array
	 */
	protected array $hints = [];

	/**
	 * All of the registered paths to JSON translation files.
	 *
	 * @var array
	 */
	protected array $jsonPaths = [];

	/**
	 * The default paths for the loader.
	 *
	 * @var array
	 */
	protected array $paths;

	/**
	 * Create a new translation file loader instance.
	 */
	public function __construct(Filesystem $files, array|string $path)
	{
		$this->files = $files;

		$this->paths = is_string($path) ? [$path] : $path;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addJsonPath(string $path): void
	{
		$this->jsonPaths[] = $path;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addNamespace(string $namespace, string $hint): void
	{
		$this->hints[$namespace] = $hint;
	}

	/**
	 * Get all registered paths for the JSON translation files.
	 */
	public function jsonPaths(): array
	{
		return $this->jsonPaths;
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(string $group, string|null $namespace = null): array
	{
		if ($group === '*' && $namespace === '*') {
			return $this->loadJsonPaths();
		}

		if (is_null($namespace) || $namespace === '*') {
			return $this->loadPaths($this->paths, $group);
		}

		return $this->loadNamespaced($group, $namespace);
	}

	/**
	 * Load and merge all JSON translation files from the registered paths.
	 *
	 * @throws \RuntimeException
	 */
	protected function loadJsonPaths(): array
	{
		return collection(array_merge($this->jsonPaths, $this->paths))
			->reduce(function ($output, $path) {
				$file = "{$path}.json";

				if ($this->files->exists($file)) {
					$contents = json_decode($this->files->get($file), true);

					if (is_null($contents) || json_last_error() !== JSON_ERROR_NONE) {
						throw new RuntimeException(
							"Translation file [$file] contains an invalid JSON structure."
						);
					}

					$output = array_merge($output, $contents);
				}

				return $output;
			}, []);
	}

	/**
	 * Load a namespaced translation group.
	 */
	protected function loadNamespaced(string $group, string $namespace): array
	{
		if (isset($this->hints[$namespace])) {
			$lines = $this->loadPaths([$this->hints[$namespace]], $group);

			return $this->loadNamespaceOverrides($lines, $group, $namespace);
		}

		return [];
	}

	/**
	 * Load a namespaced translation group for overrides.
	 */
	protected function loadNamespaceOverrides(array $lines, string $group, string $namespace): array
	{
		return collect($this->paths)
			->reduce(function ($output, $path) use ($lines, $group, $namespace) {
				$file = "{$path}/vendor/{$namespace}/{$group}.php";

				if ($this->files->exists($file)) {
					$lines = array_replace_recursive($lines, $this->files->getRequire($file));
				}

				return $lines;
			}, []);
	}

	/**
	 * Load and merge translations from files within the specified
	 * paths for a given group.
	 */
	protected function loadPaths(array $paths, string $group): array
	{
		return collect($paths)
			->reduce(function ($output, $path) use ($group) {
				$file = "{$path}/{$group}.php";

				if ($this->files->exists($file)) {
					$output = array_replace_recursive($output, $this->files->getRequire($file));
				}

				return $output;
			}, []);
	}

	/**
	 * {@inheritdoc}
	 */
	public function namespaces(): array
	{
		return $this->hints;
	}
}
