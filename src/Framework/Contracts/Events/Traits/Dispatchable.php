<?php

namespace MVPS\Lumis\Framework\Contracts\Events\Traits;

trait Dispatchable
{
	/**
	 * Dispatch the event with the provided arguments.
	 */
	public static function dispatch(): mixed
	{
		return event(new static(...func_get_args()));
	}

	/**
	 * Dispatch the event with the provided arguments
	 * if the given condition is true.
	 */
	public static function dispatchIf(bool $condition, ...$arguments): mixed
	{
		return $condition ? event(new static(...$arguments)) : null;
	}

	/**
	 * Dispatch the event with the provided arguments
	 * unless the given condition is true.
	 */
	public static function dispatchUnless(bool $condition, ...$arguments): mixed
	{
		return ! $condition ? event(new static(...$arguments)) : null;
	}

	/**
	 * Broadcast the event with the given arguments.
	 *
	 * TODO: Implement this method with broadcasting
	 */
	// public static function broadcast(): PendingBroadcast
	// {
	// 	return broadcast(new static(...func_get_args()));
	// }
}
