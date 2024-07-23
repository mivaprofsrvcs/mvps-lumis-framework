<?php

namespace MVPS\Lumis\Framework\Cache\RateLimiting;

class Limit
{
	/**
	 * The number of seconds until the rate limit is reset.
	 *
	 * @var int
	 */
	public int $decaySeconds;

	/**
	 * The rate limit signature key.
	 *
	 * @var mixed
	 */
	public mixed $key;

	/**
	 * The maximum number of attempts allowed within the given number of seconds.
	 *
	 * @var int
	 */
	public int $maxAttempts;

	/**
	 * The response generator callback.
	 *
	 * @var callable
	 */
	public $responseCallback;

	/**
	 * Create a new limit instance.
	 */
	public function __construct(mixed $key = '', int $maxAttempts = 60, int $decaySeconds = 60)
	{
		$this->key = $key;
		$this->maxAttempts = $maxAttempts;
		$this->decaySeconds = $decaySeconds;
	}

	/**
	 * Set the key of the rate limit.
	 */
	public function by(mixed $key): static
	{
		$this->key = $key;

		return $this;
	}

	/**
	 * Create a new unlimited rate limit.
	 */
	public static function none(): static
	{
		return new Unlimited;
	}

	/**
	 * Create a new rate limit using days as decay time.
	 */
	public static function perDay(int $maxAttempts, int $decayDays = 1): static
	{
		return new static('', $maxAttempts, 60 * 60 * 24 * $decayDays);
	}

	/**
	 * Create a new rate limit using hours as decay time.
	 */
	public static function perHour(int $maxAttempts, int $decayHours = 1): static
	{
		return new static('', $maxAttempts, 60 * 60 * $decayHours);
	}

	/**
	 * Create a new rate limit.
	 */
	public static function perMinute(int $maxAttempts, int $decayMinutes = 1): static
	{
		return new static('', $maxAttempts, 60 * $decayMinutes);
	}

	/**
	 * Create a new rate limit using minutes as decay time.
	 */
	public static function perMinutes(int $decayMinutes, int $maxAttempts): static
	{
		return new static('', $maxAttempts, 60 * $decayMinutes);
	}

	/**
	 * Create a new rate limit.
	 */
	public static function perSecond(int $maxAttempts, int $decaySeconds = 1): static
	{
		return new static('', $maxAttempts, $decaySeconds);
	}

	/**
	 * Set the callback that should generate the response when the limit is exceeded.
	 */
	public function response(callable $callback): static
	{
		$this->responseCallback = $callback;

		return $this;
	}
}
