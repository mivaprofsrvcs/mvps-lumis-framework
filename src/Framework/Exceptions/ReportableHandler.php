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

namespace MVPS\Lumis\Framework\Exceptions;

use Closure;
use Illuminate\Support\Traits\ReflectsClosures;
use Throwable;

class ReportableHandler
{
	use ReflectsClosures;

	/**
	 * The underlying callback.
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * Indicates if reporting should stop after invoking this handler.
	 *
	 * @var bool
	 */
	protected bool $shouldStop = false;

	/**
	 * Create a new reportable handler instance.
	 */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * Determine if the callback handles the given exception.
	 */
	public function handles(Throwable $e): bool
	{
		$callback = $this->callback instanceof Closure
			? $this->callback
			: Closure::fromCallable($this->callback);

		foreach ($this->firstClosureParameterTypes($callback) as $type) {
			if (is_a($e, $type)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Indicate that report handling should stop after invoking this callback.
	 */
	public function stop(): static
	{
		$this->shouldStop = true;

		return $this;
	}

	/**
	 * Invoke the handler.
	 */
	public function __invoke(Throwable $e): bool
	{
		$result = call_user_func($this->callback, $e);

		if ($result === false) {
			return false;
		}

		return ! $this->shouldStop;
	}
}
