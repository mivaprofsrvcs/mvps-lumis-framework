<?php

namespace MVPS\Lumis\Framework\Contracts\Http\Client;

interface Promise
{
	/**
	 * The state of a fulfilled promise.
	 *
	 * @var string
	 */
	public const FULFILLED = 'fulfilled';

	/**
	 * The initial state of the promise.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * Cancels the promise if possible.
	 *
	 * @var string
	 */
	public const REJECTED = 'rejected';

	/**
	 * Cancels the promise if possible.
	 */
	public function cancel(): void;

	/**
	 * Returns the current state of the promise.
	 *
	 * Possible states include "pending", "rejected", or "fulfilled".
	 */
	public function getState(): string;

	/**
	 * Appends a rejection handler callback to the promise, and returns a new
	 * promise resolving to the return value of the callback if it is called,
	 * or to its original fulfillment value if the promise is instead
	 * fulfilled.
	 */
	public function otherwise(callable $onRejected): Promise;

	/**
	 * Reject the promise with the given reason.
	 *
	 * @throws \RuntimeException
	 */
	public function reject(mixed $reason): void;

	/**
	 * Resolve the promise with the given value.
	 *
	 * @throws \RuntimeException
	 */
	public function resolve(mixed $value): void;

	/**
	 * Appends fulfillment and rejection handlers to the promise, and returns
	 * a new promise resolving to the return value of the called handler.
	 */
	public function then(callable|null $onFulfilled = null, callable|null $onRejected = null): Promise;

	/**
	 * Blocks execution until the promise is resolved or rejected.
	 *
	 * Optionally unwraps the promise result, returning the resolved value or
	 * throwing the rejected exception. If the promise cannot be awaited,
	 * a logic exception is thrown.
	 *
	 * @throws \LogicException
	 */
	public function wait(bool $unwrap = true): mixed;
}
