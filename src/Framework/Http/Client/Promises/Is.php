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
