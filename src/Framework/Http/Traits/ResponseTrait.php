<?php

namespace MVPS\Lumis\Framework\Http\Traits;

trait ResponseTrait
{
	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public mixed $original;

	/**
	 * Get the original response content.
	 */
	public function getOriginalContent(): mixed
	{
		$original = $this->original;

		return $original instanceof static ? $original->{__FUNCTION__}() : $original;
	}
}
