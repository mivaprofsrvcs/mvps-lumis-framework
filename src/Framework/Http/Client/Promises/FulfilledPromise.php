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

class FulfilledPromise implements PromiseContract
{
	/**
	 * The resolved value of the promise.
	 *
	 * @var mixed
	 */
	protected mixed $value;

	/**
	 * Create a new fulfilled promise instance.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(mixed $value)
	{
		if (is_object($value) && method_exists($value, 'then')) {
			throw new InvalidArgumentException(
				'You cannot create a FulfilledPromise with a promise.'
			);
		}

		$this->value = $value;
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
		return self::FULFILLED;
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
		throw new LogicException('Cannot reject a fulfilled promise');
	}

	/**
	 * {@inheritdoc}
	 */
	public function resolve($value): void
	{
		if ($value !== $this->value) {
			throw new LogicException('Cannot resolve a fulfilled promise');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function then(callable|null $onFulfilled = null, callable|null $onRejected = null): PromiseContract
	{
		if (! $onFulfilled) {
			return $this;
		}

		$queue = Utilities::queue();

		$promise = new Promise([$queue, 'run']);

		$value = $this->value;

		$queue->add(static function () use ($promise, $value, $onFulfilled): void {
			if (Is::pending($promise)) {
				try {
					$promise->resolve($onFulfilled($value));
				} catch (\Throwable $e) {
					$promise->reject($e);
				}
			}
		});

		return $promise;
	}

	/**
	 * {@inheritdoc}
	 */
	public function wait(bool $unwrap = true)
	{
		return $unwrap ? $this->value : null;
	}
}
