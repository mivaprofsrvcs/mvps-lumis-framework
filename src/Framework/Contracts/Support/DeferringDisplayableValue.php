<?php

namespace MVPS\Lumis\Framework\Contracts\Support;

interface DeferringDisplayableValue
{
	/**
	 * Resolve the displayable value that the class is deferring.
	 */
	public function resolveDisplayableValue(): Htmlable|string;
}
