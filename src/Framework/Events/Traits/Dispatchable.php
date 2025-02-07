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

namespace MVPS\Lumis\Framework\Events\Traits;

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
