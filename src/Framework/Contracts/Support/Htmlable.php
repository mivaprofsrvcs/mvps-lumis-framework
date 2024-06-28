<?php

namespace MVPS\Lumis\Framework\Contracts\Support;

interface Htmlable
{
	/**
	 * Get content as a string of HTML.
	 */
	public function toHtml(): string;
}
