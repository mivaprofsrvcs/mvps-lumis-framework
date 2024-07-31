<?php

namespace MVPS\Lumis\Framework\Http\Client\Promises;

use ArrayIterator;
use Iterator;
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise;
use MVPS\Lumis\Framework\Http\Client\Promises\Exceptions\RejectionException;
use Throwable;

class Create
{
	/**
	 * Create an exception for a rejected promise value.
	 */
	public static function exceptionFor(mixed $reason): Throwable
	{
		if ($reason instanceof Throwable) {
			return $reason;
		}

		return new RejectionException($reason);
	}

	/**
	 * Returns an iterator for the given value.
	 */
	public static function iterFor(mixed $value): Iterator
	{
		if ($value instanceof Iterator) {
			return $value;
		}

		if (is_array($value)) {
			return new ArrayIterator($value);
		}

		return new ArrayIterator([$value]);
	}

	/**
	 * Creates a promise for a value if the value is not a promise.
	 */
	public static function promiseFor(mixed $value): Promise
	{
		if ($value instanceof Promise) {
			return $value;
		}

		// Return a Guzzle promise that shadows the given promise.
		if (is_object($value) && method_exists($value, 'then')) {
			$wfn = method_exists($value, 'wait') ? [$value, 'wait'] : null;

			$cfn = method_exists($value, 'cancel') ? [$value, 'cancel'] : null;

			$promise = new Promise($wfn, $cfn);

			$value->then([$promise, 'resolve'], [$promise, 'reject']);

			return $promise;
		}

		return new FulfilledPromise($value);
	}

	/**
	 * Creates a rejected promise for a reason if the reason is not a promise.
	 * If the provided reason is a promise, then it is returned as-is.
	 */
	public static function rejectionFor($reason): Promise
	{
		if ($reason instanceof Promise) {
			return $reason;
		}

		return new RejectedPromise($reason);
	}
}
