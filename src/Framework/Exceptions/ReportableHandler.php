<?php

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
