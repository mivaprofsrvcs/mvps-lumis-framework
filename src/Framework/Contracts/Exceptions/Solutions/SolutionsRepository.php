<?php

namespace MVPS\Lumis\Framework\Contracts\Exceptions\Solutions;

use Throwable;

interface SolutionsRepository
{
	/**
	 * Extracts potential solutions from the given throwable.
	 */
	public function getFromThrowable(Throwable $throwable): array;
}
