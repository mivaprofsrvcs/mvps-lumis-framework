<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use MVPS\Lumis\Framework\Support\Str;

trait CompilesConditionals
{
	/**
	 * Identifier for the first case in the switch statement.
	 *
	 * @var bool
	 */
	protected bool $firstCaseInSwitch = true;

	/**
	 * Compile the case statements into valid PHP.
	 */
	protected function compileCase(string|null $expression = null): string
	{
		if ($this->firstCaseInSwitch) {
			$this->firstCaseInSwitch = false;

			return "case {$expression}: ?>";
		}

		return "<?php case {$expression}: ?>";
	}

	/**
	 * Compile a checked block into valid PHP.
	 */
	protected function compileChecked(string|null $condition = null): string
	{
		return "<?php if{$condition}: echo 'checked'; endif; ?>";
	}

	/**
	 * Compile the default statements in switch case into valid PHP.
	 */
	protected function compileDefault(): string
	{
		return '<?php default: ?>';
	}

	/**
	 * Compile a disabled block into valid PHP.
	 */
	protected function compileDisabled(string|null $condition = null): string
	{
		return "<?php if{$condition}: echo 'disabled'; endif; ?>";
	}

	/**
	 * Compile the else statements into valid PHP.
	 */
	protected function compileElse(): string
	{
		return '<?php else: ?>';
	}

	/**
	 * Compile the else-if statements into valid PHP.
	 */
	protected function compileElseif(string|null $expression = null): string
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile the else push statements into valid PHP.
	 */
	protected function compileElsePush(string|null $expression = null): string
	{
		return "<?php \$__env->stopPush(); else: \$__env->startPush{$expression}; ?>";
	}

	/**
	 * Compile the else-if push statements into valid PHP.
	 */
	protected function compileElsePushIf(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''), 2);

		return "<?php \$__env->stopPush(); elseif({$parts[0]}): \$__env->startPush({$parts[1]}); ?>";
	}

	/**
	 * Compile the env statements into valid PHP.
	 */
	protected function compileEnv(string|null $environments = null): string
	{
		return "<?php if(app()->environment{$environments}): ?>";
	}

	/**
	 * Compile the end-env statements into valid PHP.
	 */
	protected function compileEndEnv(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-if statements into valid PHP.
	 */
	protected function compileEndif(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-isset statements into valid PHP.
	 */
	protected function compileEndIsset(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile an end-once block into valid PHP.
	 */
	public function compileEndOnce(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-production statements into valid PHP.
	 */
	protected function compileEndProduction(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-push statements into valid PHP.
	 */
	protected function compileEndPushIf(): string
	{
		return '<?php $__env->stopPush(); endif; ?>';
	}

	/**
	 * Compile the end switch statements into valid PHP.
	 */
	protected function compileEndSwitch(): string
	{
		return '<?php endswitch; ?>';
	}

	/**
	 * Compile the end-unless statements into valid PHP.
	 */
	protected function compileEndunless(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the has-section statements into valid PHP.
	 */
	protected function compileHasSection(string|null $expression = null): string
	{
		return "<?php if (! empty(trim(\$__env->yieldContent{$expression}))): ?>";
	}

	/**
	 * Compile the if statements into valid PHP.
	 */
	protected function compileIf(string|null $expression = null): string
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile the if-isset statements into valid PHP.
	 */
	protected function compileIsset(string|null $expression = null): string
	{
		return "<?php if(isset{$expression}): ?>";
	}

	/**
	 * Compile a once block into valid PHP.
	 */
	protected function compileOnce(string|null $id = null): string
	{
		$id = $id ? $this->stripParentheses($id) : "'" . (string) Str::uuid() . "'";

		return '<?php if (! $__env->hasRenderedOnce(' . $id . ')): $__env->markAsRenderedOnce(' . $id . '); ?>';
	}

	/**
	 * Compile the production statements into valid PHP.
	 */
	protected function compileProduction(): string
	{
		return "<?php if(app()->environment('production')): ?>";
	}

	/**
	 * Compile the push statements into valid PHP.
	 */
	protected function compilePushIf(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''), 2);

		return "<?php if({$parts[0]}): \$__env->startPush({$parts[1]}); ?>";
	}

	/**
	 * Compile the section-missing statements into valid PHP.
	 */
	protected function compileSectionMissing(string|null $expression = null): string
	{
		return "<?php if (empty(trim(\$__env->yieldContent{$expression}))): ?>";
	}

	/**
	 * Compile a readonly block into valid PHP.
	 */
	protected function compileReadonly(string|null $condition = null): string
	{
		return "<?php if{$condition}: echo 'readonly'; endif; ?>";
	}

	/**
	 * Compile a required block into valid PHP.
	 */
	protected function compileRequired(string|null $condition = null): string
	{
		return "<?php if{$condition}: echo 'required'; endif; ?>";
	}

	/**
	 * Compile a selected block into valid PHP.
	 */
	protected function compileSelected(string|null $condition = null): string
	{
		return "<?php if{$condition}: echo 'selected'; endif; ?>";
	}

	/**
	 * Compile the switch statements into valid PHP.
	 */
	protected function compileSwitch(string|null $expression = null): string
	{
		$this->firstCaseInSwitch = true;

		return "<?php switch{$expression}:";
	}

	/**
	 * Compile the unless statements into valid PHP.
	 */
	protected function compileUnless(string|null $expression = null): string
	{
		return "<?php if (! {$expression}): ?>";
	}
}
