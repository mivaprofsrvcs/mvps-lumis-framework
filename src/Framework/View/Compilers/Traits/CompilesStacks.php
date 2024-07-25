<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use MVPS\Lumis\Framework\Support\Str;

trait CompilesStacks
{
	/**
	 * Compile the end-prepend statements into valid PHP.
	 */
	protected function compileEndprepend(): string
	{
		return '<?php $__env->stopPrepend(); ?>';
	}

	/**
	 * Compile the end-prepend-once statements into valid PHP.
	 */
	protected function compileEndprependOnce(): string
	{
		return '<?php $__env->stopPrepend(); endif; ?>';
	}

	/**
	 * Compile the end-push statements into valid PHP.
	 */
	protected function compileEndpush(): string
	{
		return '<?php $__env->stopPush(); ?>';
	}

	/**
	 * Compile the end-push-once statements into valid PHP.
	 */
	protected function compileEndpushOnce(): string
	{
		return '<?php $__env->stopPush(); endif; ?>';
	}

	/**
	 * Compile the prepend statements into valid PHP.
	 */
	protected function compilePrepend(string|null $expression = null): string
	{
		return "<?php \$__env->startPrepend{$expression}; ?>";
	}

	/**
	 * Compile the prepend-once statements into valid PHP.
	 */
	protected function compilePrependOnce(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''), 2);

		[$stack, $id] = [$parts[0], $parts[1] ?? ''];

		$id = trim($id) ?: "'" . (string) Str::uuid() . "'";

		return '<?php if (! $__env->hasRenderedOnce(' . $id . ')): $__env->markAsRenderedOnce(' . $id . ');
$__env->startPrepend(' . $stack . '); ?>';
	}

	/**
	 * Compile the push statements into valid PHP.
	 */
	protected function compilePush(string|null $expression = null): string
	{
		return "<?php \$__env->startPush{$expression}; ?>";
	}

	/**
	 * Compile the push-once statements into valid PHP.
	 */
	protected function compilePushOnce(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''), 2);

		[$stack, $id] = [$parts[0], $parts[1] ?? ''];

		$id = trim($id) ?: "'" . (string) Str::uuid() . "'";

		return '<?php if (! $__env->hasRenderedOnce(' . $id . ')): $__env->markAsRenderedOnce(' . $id . ');
$__env->startPush(' . $stack . '); ?>';
	}

	/**
	 * Compile the stack statements into the content.
	 */
	protected function compileStack(string|null $expression = null): string
	{
		return "<?php echo \$__env->yieldPushContent{$expression}; ?>";
	}
}
