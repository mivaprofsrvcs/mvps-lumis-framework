<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesFragments
{
	/**
	 * The last compiled fragment.
	 *
	 * @var string
	 */
	protected string $lastFragment;

	/**
	 * Compile the end-fragment statements into valid PHP.
	 */
	protected function compileEndfragment(): string
	{
		return '<?php echo $__env->stopFragment(); ?>';
	}

	/**
	 * Compile the fragment statements into valid PHP.
	 */
	protected function compileFragment(string $expression): string
	{
		$this->lastFragment = trim($expression, "()'\" ");

		return "<?php \$__env->startFragment{$expression}; ?>";
	}
}
