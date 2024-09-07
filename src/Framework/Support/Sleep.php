<?php

namespace MVPS\Lumis\Framework\Support;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

class Sleep
{
	use Macroable;

	/**
	 * The total duration to sleep.
	 *
	 * @var \Carbon\CarbonInterval
	 */
	public CarbonInterval $duration;

	/**
	 * The pending duration to sleep.
	 *
	 * @var int|float|null
	 */
	protected int|float|null $pending = null;

	/**
	 * The sequence of sleep durations encountered while faking.
	 *
	 * @var array<int, \Carbon\CarbonInterval>
	 */
	protected static array $sequence = [];

	/**
	 * Indicates if the instance should sleep.
	 *
	 * @var bool
	 */
	protected bool $shouldSleep = true;

	/**
	 * Keep Carbon's "now" in sync when sleeping.
	 *
	 * @var bool
	 */
	protected static bool $syncWithCarbon = false;

	/**
	 * Create a new sleep instance.
	 */
	public function __construct(int|float|DateInterval $duration)
	{
		$this->duration($duration);
	}

	/**
	 * Add additional time to sleep for.
	 */
	public function and(int|float $duration): static
	{
		$this->pending = $duration;

		return $this;
	}

	/**
	 * Sleep for the given duration. Replaces any previously defined duration.
	 */
	protected function duration(DateInterval|int|float $duration): static
	{
		if (! $duration instanceof DateInterval) {
			$this->duration = CarbonInterval::microsecond(0);

			$this->pending = $duration;
		} else {
			$duration = CarbonInterval::instance($duration);

			if ($duration->totalMicroseconds < 0) {
				$duration = CarbonInterval::seconds(0);
			}

			$this->duration = $duration;
			$this->pending = null;
		}

		return $this;
	}

	/**
	 * Sleep for the given duration.
	 */
	public static function for(DateInterval|int|float $duration): static
	{
		return new static($duration);
	}

	/**
	 * Sleep for on microsecond.
	 */
	public function microsecond(): static
	{
		return $this->microseconds();
	}

	/**
	 * Sleep for the given number of microseconds.
	 */
	public function microseconds(): static
	{
		$this->duration->add('microseconds', $this->pullPending());

		return $this;
	}

	/**
	 * Sleep for one millisecond.
	 */
	public function millisecond(): static
	{
		return $this->milliseconds();
	}

	/**
	 * Sleep for the given number of milliseconds.
	 */
	public function milliseconds(): static
	{
		$this->duration->add('milliseconds', $this->pullPending());

		return $this;
	}

	/**
	 * Sleep for one minute.
	 */
	public function minute(): static
	{
		return $this->minutes();
	}

	/**
	 * Sleep for the given number of minutes.
	 */
	public function minutes(): static
	{
		$this->duration->add('minutes', $this->pullPending());

		return $this;
	}

	/**
	 * Resolve the pending duration.
	 */
	protected function pullPending(): int|float
	{
		if (is_null($this->pending)) {
			$this->shouldNotSleep();

			throw new RuntimeException('No duration specified.');
		}

		if ($this->pending < 0) {
			$this->pending = 0;
		}

		return tap($this->pending, function () {
			$this->pending = null;
		});
	}

	/**
	 * Sleep for one second.
	 */
	public function second(): static
	{
		return $this->seconds();
	}

	/**
	 * Sleep for the given number of seconds.
	 */
	public function seconds(): static
	{
		$this->duration->add('seconds', $this->pullPending());

		return $this;
	}

	/**
	 * Indicate that the instance should not sleep.
	 */
	protected function shouldNotSleep(): static
	{
		$this->shouldSleep = false;

		return $this;
	}

	/**
	 * Sleep for the given number of seconds.
	 */
	public static function sleep(int|float $duration): static
	{
		return (new static($duration))->seconds();
	}

	/**
	 * Indicate that Carbon's "now" should be kept in sync when sleeping.
	 */
	public static function syncWithCarbon($value = true): void
	{
		static::$syncWithCarbon = $value;
	}

	/**
	 * Sleep until the given timestamp.
	 */
	public static function until(DateTimeInterface|int|float|string $timestamp): static
	{
		if (is_numeric($timestamp)) {
			$timestamp = Carbon::createFromTimestamp($timestamp, date_default_timezone_get());
		}

		return new static(Carbon::now()->diff($timestamp));
	}

	/**
	 * Sleep for the given number of microseconds.
	 */
	public static function usleep(int $duration): static
	{
		return (new static($duration))->microseconds();
	}

	/**
	 * Only sleep when the given condition is true.
	 */
	public function when(Closure|bool $condition): static
	{
		$this->shouldSleep = (bool) value($condition, $this);

		return $this;
	}

	/**
	 * Don't sleep when the given condition is true.
	 */
	public function unless(Closure|bool $condition): static
	{
		return $this->when(! value($condition, $this));
	}

	/**
	 * Handle the object's destruction.
	 */
	public function __destruct()
	{
		if (! $this->shouldSleep) {
			return;
		}

		if (! is_null($this->pending)) {
			throw new RuntimeException('Unknown duration unit.');
		}

		$remaining = $this->duration->copy();

		$seconds = (int) $remaining->totalSeconds;

		if ($seconds > 0) {
			sleep($seconds);

			$remaining = $remaining->subSeconds($seconds);
		}

		$microseconds = (int) $remaining->totalMicroseconds;

		if ($microseconds > 0) {
			usleep($microseconds);
		}
	}
}
