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

use InvalidArgumentException;
use LogicException;
use MVPS\Lumis\Framework\Contracts\Http\Client\Promise as PromiseContract;
use Throwable;

class RejectedPromise implements PromiseContract
{
	protected $reason;

	/**
	 * Create a new rejected promise instance.
	 */
	public function __construct($reason)
	{
		if (is_object($reason) && method_exists($reason, 'then')) {
			throw new InvalidArgumentException(
				'You cannot create a RejectedPromise with a promise.'
			);
		}

		$this->reason = $reason;
	}

	/**
	 * {@inheritdoc}
	 */
	public function cancel(): void
	{
	}

	/**
	 * {@inheritdoc}
	 */
	public function getState(): string
	{
		return self::REJECTED;
	}

	/**
	 * {@inheritdoc}
	 */
	public function otherwise(callable $onRejected): PromiseContract
	{
		return $this->then(null, $onRejected);
	}

	/**
	 * {@inheritdoc}
	 */
	public function reject($reason): void
	{
		if ($reason !== $this->reason) {
			throw new LogicException('Cannot reject a rejected promise');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function resolve($value): void
	{
		throw new LogicException('Cannot resolve a rejected promise');
	}

	/**
	 * {@inheritdoc}
	 */
	public function then(callable|null $onFulfilled = null, callable|null $onRejected = null): PromiseContract
	{
		if (! $onRejected) {
			return $this;
		}

		$queue = Utilities::queue();

		$reason = $this->reason;

		$promise = new Promise([$queue, 'run']);

		$queue->add(static function () use ($promise, $reason, $onRejected): void {
			if (Is::pending($promise)) {
				try {
					$promise->resolve($onRejected($reason));
				} catch (Throwable $e) {
					$promise->reject($e);
				}
			}
		});

		return $promise;
	}

	/**
	 * {@inheritdoc}
	 */
	public function wait(bool $unwrap = true): mixed
	{
		if ($unwrap) {
			throw Create::exceptionFor($this->reason);
		}

		return null;
	}
}
