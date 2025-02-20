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

namespace MVPS\Lumis\Framework\Debugging\Traits;

use Throwable;

trait ResolvesDumpSource
{
	/**
	 * List of files that require special trace handling mapped to their levels.
	 *
	 * @var array
	 */
	protected static array $adjustableTraces = [
		'symfony/var-dumper/Resources/functions/dump.php' => 1,
		'Illuminate/Collections/Traits/EnumeratesValues.php' => 4,
	];

	/**
	 * List of href formats for common editors.
	 *
	 * @var array
	 */
	protected array $editorHrefs = [
		'atom' => 'atom://core/open/file?filename={file}&line={line}',
		'emacs' => 'emacs://open?url=file://{file}&line={line}',
		'idea' => 'idea://open?file={file}&line={line}',
		'macvim' => 'mvim://open/?url=file://{file}&line={line}',
		'netbeans' => 'netbeans://open/?f={file}:{line}',
		'nova' => 'nova://core/open/file?filename={file}&line={line}',
		'phpstorm' => 'phpstorm://open?file={file}&line={line}',
		'sublime' => 'subl://open?url=file://{file}&line={line}',
		'textmate' => 'txmt://open?url=file://{file}&line={line}',
		'vscode' => 'vscode://file/{file}:{line}',
		'vscode-insiders' => 'vscode-insiders://file/{file}:{line}',
		'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/{file}:{line}',
		'vscode-remote' => 'vscode://vscode-remote/{file}:{line}',
		'vscodium' => 'vscodium://file/{file}:{line}',
		'xdebug' => 'xdebug://{file}@{line}',
	];

	/**
	 * The dump source resolver.
	 *
	 * @var callable|bool|null
	 */
	protected static mixed $dumpSourceResolver = null;

	/**
	 * Don't include the location / file of the dump in dumps.
	 */
	public static function dontIncludeSource(): void
	{
		static::$dumpSourceResolver = false;
	}

	/**
	 * Get the original view compiled file by the given compiled file.
	 */
	protected function getOriginalFileForCompiledView(string $file): string
	{
		preg_match('/\/\*\*PATH\s(.*)\sENDPATH/', file_get_contents($file), $matches);

		if (isset($matches[1])) {
			$file = $matches[1];
		}

		return $file;
	}

	/**
	 * Determine if the given file is a view compiled.
	 */
	protected function isCompiledViewFile(string $file): bool
	{
		return str_starts_with($file, $this->compiledViewPath) && str_ends_with($file, '.php');
	}

	/**
	 * Resolve the source of the dump call.
	 */
	public function resolveDumpSource(): array|null
	{
		if (static::$dumpSourceResolver === false) {
			return null;
		}

		if (static::$dumpSourceResolver) {
			return call_user_func(static::$dumpSourceResolver);
		}

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

		$sourceKey = null;

		foreach ($trace as $traceKey => $traceFile) {
			if (! isset($traceFile['file'])) {
				continue;
			}

			foreach (self::$adjustableTraces as $name => $key) {
				if (str_ends_with($traceFile['file'], str_replace('/', DIRECTORY_SEPARATOR, $name))) {
					$sourceKey = $traceKey + $key;

					break;
				}
			}

			if (! is_null($sourceKey)) {
				break;
			}
		}

		if (is_null($sourceKey)) {
			return null;
		}

		$file = $trace[$sourceKey]['file'] ?? null;
		$line = $trace[$sourceKey]['line'] ?? null;

		if (is_null($file) || is_null($line)) {
			return null;
		}

		$relativeFile = $file;

		if ($this->isCompiledViewFile($file)) {
			$file = $this->getOriginalFileForCompiledView($file);
			$line = null;
		}

		if (str_starts_with($file, $this->basePath)) {
			$relativeFile = substr($file, strlen($this->basePath) + 1);
		}

		return [$file, $relativeFile, $line];
	}

	/**
	 * Set the resolver that resolves the source of the dump call.
	 */
	public static function resolveDumpSourceUsing(callable|null $callable): void
	{
		static::$dumpSourceResolver = $callable;
	}

	/**
	 * Resolve the source href of the dump.
	 */
	protected function resolveSourceHref(string $file, int|null $line): string|null
	{
		try {
			$editor = config('app.editor');
		} catch (Throwable) {
			//
		}

		if (! isset($editor)) {
			return null;
		}

		$href = is_array($editor) && isset($editor['href'])
			? $editor['href']
			: $this->editorHrefs[$editor['name'] ?? $editor] ??
				sprintf('%s://open?file={file}&line={line}', $editor['name'] ?? $editor);

		$basePath = $editor['base_path'] ?? false;

		if ($basePath) {
			$file = str_replace($this->basePath, $basePath, $file);
		}

		$href = str_replace(
			['{file}', '{line}'],
			[$file, is_null($line) ? 1 : $line],
			$href,
		);

		return $href;
	}
}
