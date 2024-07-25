<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use MVPS\Lumis\Framework\Support\Js;

trait CompilesJs
{
	/**
	 * Compile the "@js" directive into valid PHP.
	 */
	protected function compileJs(string|null $expression = null): string
	{
		return sprintf(
			"<?php echo \%s::from(%s)->toHtml() ?>",
			Js::class,
			$this->stripParentheses($expression ?? '')
		);
	}
}
