<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesComments
{
	/**
	 * Compile Blade comments into an empty string.
	 */
	protected function compileComments(string $value): string
	{
		$pattern = sprintf('/%s--(.*?)--%s/s', $this->contentTags[0], $this->contentTags[1]);

		return preg_replace($pattern, '', $value);
	}
}
