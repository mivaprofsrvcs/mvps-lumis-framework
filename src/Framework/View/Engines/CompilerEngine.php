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

namespace MVPS\Lumis\Framework\View\Engines;

use Illuminate\Database\RecordsNotFoundException;
use MVPS\Lumis\Framework\Contracts\View\Compiler;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use MVPS\Lumis\Framework\Http\Exceptions\HttpException;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\View\Exceptions\ViewException;
use Throwable;

class CompilerEngine extends PhpEngine
{
	/**
	 * The view paths that were compiled or are not expired, keyed by the path.
	 *
	 * @var array<string, true>
	 */
	protected array $compiledOrNotExpired = [];

	/**
	 * The Blade compiler instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Compiler
	 */
	protected Compiler $compiler;

	/**
	 * A stack of the last compiled templates.
	 *
	 * @var array
	 */
	protected array $lastCompiled = [];

	/**
	 * Create a new compiler engine instance.
	 */
	public function __construct(Compiler $compiler, Filesystem|null $files = null)
	{
		parent::__construct($files ?: new Filesystem);

		$this->compiler = $compiler;
	}

	/**
	 * Clear the cache of views that were compiled or not expired.
	 */
	public function forgetCompiledOrNotExpired(): void
	{
		$this->compiledOrNotExpired = [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($path, array $data = []): string
	{
		$this->lastCompiled[] = $path;

		// If the given view has expired (i.e., it has been edited since it was
		// last compiled), we will recompile the view to ensure that we are
		// evaluating the most recent version. We'll pass the path of the view
		// to the compiler for this purpose.
		if (! isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
			$this->compiler->compile($path);
		}

		// After obtaining the path to the compiled file, we will evaluate it
		// using standard PHP as we would with any other template. Additionally,
		// we maintain a stack of rendered views to ensure that any exception
		// messages generated are accurate and informative.
		try {
			$results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
		} catch (ViewException $e) {
			if (! str($e->getMessage())->contains(['No such file or directory', 'File does not exist at path'])) {
				throw $e;
			}

			if (! isset($this->compiledOrNotExpired[$path])) {
				throw $e;
			}

			$this->compiler->compile($path);

			$results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
		}

		$this->compiledOrNotExpired[$path] = true;

		array_pop($this->lastCompiled);

		return $results;
	}

	/**
	 * Get the compiler implementation.
	 */
	public function getCompiler(): Compiler
	{
		return $this->compiler;
	}

	/**
	 * Get the exception message for an exception.
	 */
	protected function getMessage(Throwable $e): string
	{
		return $e->getMessage() . ' (View: ' . realpath(last($this->lastCompiled)) . ')';
	}

	/**
	 * Handle a view exception.
	 *
	 * @throws \Throwable
	 */
	protected function handleViewException(Throwable $e, $obLevel): void
	{
		if (
			$e instanceof HttpException ||
			$e instanceof HttpResponseException ||
			$e instanceof RecordsNotFoundException
		) {
			parent::handleViewException($e, $obLevel);
		}

		$e = new ViewException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

		parent::handleViewException($e, $obLevel);
	}
}
