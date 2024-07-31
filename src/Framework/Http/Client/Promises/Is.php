<?php

namespace MVPS\Lumis\Framework\Http\Client\Promises;

use MVPS\Lumis\Framework\Contracts\Http\Client\Promise as PromiseContract;

class Is
{
	/**
	 * Determines if a promise is fulfilled.
	 */
	public static function fulfilled(PromiseContract $promise): bool
	{
		return $promise->getState() === PromiseContract::FULFILLED;
	}

	/**
	 * Determines if a promise is pending.
	 */
	public static function pending(PromiseContract $promise): bool
	{
		return $promise->getState() === PromiseContract::PENDING;
	}

	/**
	 * Determines if a promise is rejected.
	 */
	public static function rejected(PromiseContract $promise): bool
	{
		return $promise->getState() === PromiseContract::REJECTED;
	}

	/**
	 * Determines if a promise is fulfilled or rejected.
	 */
	public static function settled(PromiseContract $promise): bool
	{
		return $promise->getState() !== PromiseContract::PENDING;
	}
}
