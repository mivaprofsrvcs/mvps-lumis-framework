<?php

namespace MVPS\Lumis\Framework\Routing\Exceptions;

use RuntimeException;

class BackedEnumCaseNotFoundException extends RuntimeException
{
	/**
	 * Create a new backed enum case not found exception instance.
	 */
	public function __construct(string $backedEnumClass, string $case)
	{
		parent::__construct("Case [{$case}] not found on Backed Enum [{$backedEnumClass}].");
	}
}
