<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use Throwable;

trait ResponseTrait
{
	/**
	 * The exception that triggered the error response (if applicable).
	 *
	 * @var \Throwable|null
	 */
	public Throwable|null $exception = null;

	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public mixed $original = null;

	/**
	 * Get the original response content.
	 */
	public function getOriginalContent(): mixed
	{
		$original = $this->original;

		return $original instanceof static ? $original->{__FUNCTION__}() : $original;
	}

	/**
	 * Set the exception to attach to the response.
	 */
	public function withException(Throwable $e): static
	{
		$this->exception = $e;

		return $this;
	}
}
