<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesErrors
{
	/**
	 * Compile the enderror statements into valid PHP.
	 */
	protected function compileEnderror(): string
	{
		return '<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';
	}

	/**
	 * Compile the error statements into valid PHP.
	 */
	protected function compileError(string|null $expression = null): string
	{
		$expression = $this->stripParentheses($expression ?? '');

		return '<?php $__errorArgs = [' . $expression . '];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>';
	}
}
