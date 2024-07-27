<?php

namespace MVPS\Lumis\Framework\Exceptions\Solutions;

use MVPS\Lumis\Framework\Contracts\Exceptions\Solutions\SolutionsRepository;
use Throwable;

class NullSolutionsRepository implements SolutionsRepository
{
	/**
	 * {@inheritdoc}
	 */
	public function getFromThrowable(Throwable $throwable): array
	{
		return [];
	}
}
