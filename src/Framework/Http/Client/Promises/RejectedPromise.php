<?php

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
