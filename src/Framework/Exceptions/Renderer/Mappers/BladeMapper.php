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

namespace MVPS\Lumis\Framework\Exceptions\Renderer\Mappers;

use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Contracts\View\Factory;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\View\Compilers\BladeCompiler;
use MVPS\Lumis\Framework\View\Exceptions\ViewException;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

class BladeMapper
{
	/**
	 * The Blade compiler instance.
	 *
	 * @var \MVPS\Lumis\Framework\View\Compilers\BladeCompiler
	 */
	protected BladeCompiler $bladeCompiler;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Factory
	 */
	protected Factory $factory;

	/**
	 * Create a new Blade mapper instance.
	 */
	public function __construct(Factory $factory, BladeCompiler $bladeCompiler)
	{
		$this->factory = $factory;
		$this->bladeCompiler = $bladeCompiler;
	}

	/**
	 * Add line numbers to blade components.
	 */
	protected function addBladeComponentLineNumbers(string $value): string
	{
		$shouldInsertLineNumbers = preg_match_all(
			'/<\s*x[-:]([\w\-:.]*)/mx',
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		);

		if ($shouldInsertLineNumbers) {
			foreach (array_reverse($matches[0]) as $match) {
				$position = mb_strlen(substr($value, 0, $match[1]));

				$value = $this->insertLineNumberAtPosition($position, $value);
			}
		}

		return $value;
	}

	/**
	 * Add line numbers to echo statements.
	 */
	protected function addEchoLineNumbers(string $value): string
	{
		$echoPairs = [['{{', '}}'], ['{{{', '}}}'], ['{!!', '!!}']];

		foreach ($echoPairs as $pair) {
			// Matches {{ $value }}, {!! $value !!} and  {{{ $value }}} depending on $pair.
			$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $pair[0], $pair[1]);

			if (preg_match_all($pattern, $value, $matches, PREG_OFFSET_CAPTURE)) {
				foreach (array_reverse($matches[0]) as $match) {
					$position = mb_strlen(substr($value, 0, $match[1]));

					$value = $this->insertLineNumberAtPosition($position, $value);
				}
			}
		}

		return $value;
	}

	/**
	 * Add line numbers to blade statements.
	 */
	protected function addStatementLineNumbers(string $value): string
	{
		$shouldInsertLineNumbers = preg_match_all(
			'/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		);

		if ($shouldInsertLineNumbers) {
			foreach (array_reverse($matches[0]) as $match) {
				$position = mb_strlen(substr($value, 0, $match[1]));

				$value = $this->insertLineNumberAtPosition($position, $value);
			}
		}

		return $value;
	}

	/**
	 * Compile the source map for the given blade file.
	 */
	protected function compileSourcemap(string $value): string
	{
		try {
			$value = $this->addEchoLineNumbers($value);
			$value = $this->addStatementLineNumbers($value);
			$value = $this->addBladeComponentLineNumbers($value);

			$value = $this->bladeCompiler->compileString($value);

			return $this->trimEmptyLines($value);
		} catch (Throwable $e) {
			report($e);

			return $value;
		}
	}

	/**
	 * Detect the line number in the original blade file.
	 */
	protected function detectLineNumber(string $filename, int $compiledLineNumber): int
	{
		$map = $this->compileSourcemap((string) file_get_contents($filename));

		return $this->findClosestLineNumberMapping($map, $compiledLineNumber);
	}

	/**
	 * Filter out the view data that should not be shown in the exception report.
	 */
	protected function filterViewData(array $data): array
	{
		return array_filter($data, function ($value, $key) {
			if ($key === 'app') {
				return ! $value instanceof Application;
			}

			return $key !== '__env';
		}, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * Find the closest line number mapping in the given source map.
	 */
	protected function findClosestLineNumberMapping(string $map, int $compiledLineNumber): int
	{
		$map = explode("\n", $map);

		$maxDistance = 20;

		$pattern = '/\|---LINE:(?P<line>[0-9]+)---\|/m';

		$lineNumberToCheck = $compiledLineNumber - 1;

		while (true) {
			if ($lineNumberToCheck < $compiledLineNumber - $maxDistance) {
				return min($compiledLineNumber, count($map));
			}

			if (preg_match($pattern, $map[$lineNumberToCheck] ?? '', $matches)) {
				return (int) $matches['line'];
			}

			$lineNumberToCheck--;
		}
	}

	/**
	 * Find the compiled view file for the given compiled path.
	 */
	protected function findCompiledView(string $compiledPath): string|null
	{
		return once(fn () => $this->getKnownPaths())[$compiledPath] ?? null;
	}

	/**
	 * Get the list of known paths from the compiler engine.
	 */
	protected function getKnownPaths(): array
	{
		$compilerEngineReflection = new ReflectionClass(
			$bladeCompilerEngine = $this->factory->getEngineResolver()->resolve('blade'),
		);

		if (
			! $compilerEngineReflection->hasProperty('lastCompiled') &&
			$compilerEngineReflection->hasProperty('engine')
		) {
			$compilerEngine = $compilerEngineReflection->getProperty('engine');

			$compilerEngine->setAccessible(true);

			$compilerEngine = $compilerEngine->getValue($bladeCompilerEngine);

			$lastCompiled = new ReflectionProperty($compilerEngine, 'lastCompiled');

			$lastCompiled->setAccessible(true);

			$lastCompiled = $lastCompiled->getValue($compilerEngine);
		} else {
			$lastCompiled = $compilerEngineReflection->getProperty('lastCompiled');

			$lastCompiled->setAccessible(true);

			$lastCompiled = $lastCompiled->getValue($bladeCompilerEngine);
		}

		$knownPaths = [];

		foreach ($lastCompiled as $lastCompiledPath) {
			$compiledPath = $bladeCompilerEngine->getCompiler()->getCompiledPath($lastCompiledPath);

			$knownPaths[realpath($compiledPath ?? $lastCompiledPath)] = realpath($lastCompiledPath);
		}

		return $knownPaths;
	}

	/**
	 * Insert a line number at the given position.
	 */
	protected function insertLineNumberAtPosition(int $position, string $value): string
	{
		$before = mb_substr($value, 0, $position);

		$lineNumber = count(explode("\n", $before));

		return mb_substr($value, 0, $position) . "|---LINE:{$lineNumber}---|" . mb_substr($value, $position);
	}

	/**
	 * Map cached view paths to their original paths.
	 */
	public function map(FlattenException $exception)
	{
		while ($exception->getClass() === ViewException::class) {
			$previous = $exception->getPrevious();

			if (is_null($previous)) {
				break;
			}

			$exception = $previous;
		}

		$trace = Collection::make($exception->getTrace())
			->map(function ($frame) {
				if ($originalPath = $this->findCompiledView((string) Arr::get($frame, 'file', ''))) {
					$frame['file'] = $originalPath;
					$frame['line'] = $this->detectLineNumber($frame['file'], $frame['line']);
				}

				return $frame;
			})->toArray();

		return tap($exception, fn () => (fn () => $this->trace = $trace)->call($exception));
	}

	/**
	 * Trim empty lines from the given value.
	 */
	protected function trimEmptyLines(string $value): string
	{
		$value = preg_replace('/^\|---LINE:([0-9]+)---\|$/m', '', $value);

		return ltrim((string) $value, PHP_EOL);
	}
}
