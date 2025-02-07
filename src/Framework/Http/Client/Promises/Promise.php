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

use LogicException;
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise as PromiseContract;
use MVPS\Lumis\Framework\Http\Client\Promises\Exceptions\CancellationException;
use Throwable;

class Promise implements PromiseContract
{
	/**
	 * The function to call to cancel the promise.
	 *
	 * @var callable|null
	 */
	protected $cancelFn;

	/**
	 * An array of registered handlers.
	 *
	 * @var array
	 */
	protected array $handlers = [];

	/**
	 * The resolved or rejected value of the promise.
	 *
	 * @var mixed
	 */
	protected mixed $result;

	/**
	 * The current state of the promise.
	 *
	 * @var string
	 */
	protected string $state = self::PENDING;

	/**
	 * The function to call to wait for the promise to resolve.
	 *
	 * @var callable|null
	 */
	protected $waitFn;

	/**
	 * A list of functions to call when the promise is resolved or rejected.
	 *
	 * @var array|null
	 */
	protected array|null $waitList = null;

	/**
	 * Create a new promise instance.
	 */
	public function __construct(callable|null $waitFn = null, callable|null $cancelFn = null)
	{
		$this->waitFn = $waitFn;
		$this->cancelFn = $cancelFn;
	}

	/**
	 * Call a stack of handlers using a specific callback index and value.
	 */
	protected static function callHandler(int $index, mixed $value, array $handler): void
	{
		$promise = $handler[0];

		if (Is::settled($promise)) {
			return;
		}

		try {
			if (isset($handler[$index])) {
				// To prevent a circular reference and potential memory leak,
				// clear the handler variable as it's no longer needed after
				// the function call.
				$f = $handler[$index];

				unset($handler);

				$promise->resolve($f($value));
			} elseif ($index === 1) {
				// Forward resolution values as-is.
				$promise->resolve($value);
			} else {
				// Forward rejections down the chain.
				$promise->reject($value);
			}
		} catch (Throwable $reason) {
			$promise->reject($reason);
		}
	}

	/**
	 * Cancels the promise if possible.
	 */
	public function cancel(): void
	{
		if ($this->state !== static::PENDING) {
			return;
		}

		$this->waitFn = $this->waitList = null;

		if ($this->cancelFn) {
			$fn = $this->cancelFn;

			$this->cancelFn = null;

			try {
				$fn();
			} catch (Throwable $e) {
				$this->reject($e);
			}
		}

		if ($this->state === static::PENDING) {
			$this->reject(new CancellationException('Promise has been cancelled'));
		}
	}

	/**
	 * Returns the current state of the promise.
	 *
	 * Possible states include "pending", "rejected", or "fulfilled".
	 */
	public function getState(): string
	{
		return $this->state;
	}

	/**
	 * Invokes the waiting function, handling potential exceptions.
	 */
	protected function invokeWaitFn(): void
	{
		try {
			$wfn = $this->waitFn;

			$this->waitFn = null;

			$wfn(true);
		} catch (Throwable $reason) {
			if ($this->state === static::PENDING) {
				$this->reject($reason);
			} else {
				throw $reason;
			}
		}
	}

	/**
	 * Invokes waiting functions in the wait list.
	 */
	protected function invokeWaitList(): void
	{
		$waitList = $this->waitList;

		$this->waitList = null;

		foreach ($waitList as $result) {
			do {
				$result->waitIfPending();

				$result = $result->result;
			} while ($result instanceof Promise);

			if ($result instanceof PromiseContract) {
				$result->wait(false);
			}
		}
	}

	/**
	 * Creates a new promise that resolves to the result of the rejection handler.
	 */
	public function otherwise(callable $onRejected): PromiseContract
	{
		return $this->then(null, $onRejected);
	}

	/**
	 * Resolves the promise with the given value.
	 */
	public function resolve(mixed $value): void
	{
		$this->settle(static::FULFILLED, $value);
	}

	/**
	 * Rejects the promise with the given reason.
	 */
	public function reject(mixed $reason): void
	{
		$this->settle(static::REJECTED, $reason);
	}

	/**
	 * Sets the promise state and handles subsequent actions.
	 *
	 * @throws \LogicException If the promise is already settled or if there is an invalid state transition.
	 */
	protected function settle(string $state, mixed $value): void
	{
		if ($this->state !== static::PENDING) {
			if ($state === $this->state && $value === $this->result) {
				return;
			}

			throw $this->state === $state
				? new LogicException("The promise is already {$state}.")
				: new LogicException("Cannot change a {$this->state} promise to {$state}");
		}

		if ($value === $this) {
			throw new LogicException('Cannot fulfill or reject a promise with itself');
		}

		// Reset promise state while preserving registered handlers.
		$this->state = $state;
		$this->result = $value;

		$handlers = $this->handlers;

		$this->handlers = null;
		$this->waitFn = null;
		$this->waitList = $this->waitFn;
		$this->cancelFn = null;

		if (! $handlers) {
			return;
		}

		if (! is_object($value) || ! method_exists($value, 'then')) {
			$id = $state === static::FULFILLED ? 1 : 2;

			Utilities::queue()->add(static function () use ($id, $value, $handlers): void {
				foreach ($handlers as $handler) {
					static::callHandler($id, $value, $handler);
				}
			});
		} elseif ($value instanceof Promise && Is::pending($value)) {
			$value->handlers = array_merge($value->handlers, $handlers);
		} else {
			$value->then(
				static function ($value) use ($handlers): void {
					foreach ($handlers as $handler) {
						static::callHandler(1, $value, $handler);
					}
				},
				static function ($reason) use ($handlers): void {
					foreach ($handlers as $handler) {
						static::callHandler(2, $reason, $handler);
					}
				}
			);
		}
	}

	/**
	 * Attaches fulfillment and rejection handlers to the promise.
	 */
	public function then(callable|null $onFulfilled = null, callable|null $onRejected = null): PromiseContract
	{
		if ($this->state === static::PENDING) {
			$p = new Promise(null, [$this, 'cancel']);

			$this->handlers[] = [$p, $onFulfilled, $onRejected];

			$p->waitList = $this->waitList;
			$p->waitList[] = $this;

			return $p;
		}

		// Return a fulfilled promise and immediately invoke any callbacks.
		if ($this->state === static::FULFILLED) {
			$promise = Create::promiseFor($this->result);

			return $onFulfilled ? $promise->then($onFulfilled) : $promise;
		}

		// The promise is already in a terminal state (cancelled or rejected),
		// so create a rejected promise and execute any pending handlers.
		$rejection = Create::rejectionFor($this->result);

		return $onRejected ? $rejection->then(null, $onRejected) : $rejection;
	}

	/**
	 * Blocks execution until the promise is settled.
	 *
	 * @throws \LogicException If the promise cannot be awaited or does not settle.
	 */
	public function wait(bool $unwrap = true): mixed
	{
		$this->waitIfPending();

		if ($this->result instanceof PromiseContract) {
			return $this->result->wait($unwrap);
		}
		if ($unwrap) {
			if ($this->state === static::FULFILLED) {
				return $this->result;
			}

			throw Create::exceptionFor($this->result);
		}
	}

	/**
	 * Waits for the promise to be settled.
	 *
	 * If the promise is pending, attempts to resolve it by invoking the wait
	 * function or processing the wait list. If no resolution occurs, rejects
	 * the promise with an appropriate error message.
	 */
	protected function waitIfPending(): void
	{
		if ($this->state !== static::PENDING) {
			return;
		} elseif ($this->waitFn) {
			$this->invokeWaitFn();
		} elseif ($this->waitList) {
			$this->invokeWaitList();
		} else {
			// If there's no wait function, then reject the promise.
			$this->reject('Cannot wait on a promise that has '
				. 'no internal wait function. You must provide a wait '
				. 'function when constructing the promise to be able to '
				. 'wait on a promise.');
		}

		Utilities::queue()->run();

		if ($this->state === static::PENDING) {
			$this->reject('Invoking the wait callback did not resolve the promise');
		}
	}
}
