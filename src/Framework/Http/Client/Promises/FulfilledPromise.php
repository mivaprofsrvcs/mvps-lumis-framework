<?php

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
