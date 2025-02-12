<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use Illuminate\Contracts\Support\CanBeEscapedWhenCastToString;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\View\AnonymousComponent;
use MVPS\Lumis\Framework\View\ComponentAttributeBag;

trait CompilesComponents
{
	/**
	 * The component name hash stack.
	 *
	 * @var array
	 */
	protected static array $componentHashStack = [];

	/**
	 * Compile the aware statement into valid PHP.
	 */
	protected function compileAware(string|null $expression = null): string
	{
		return "<?php foreach ({$expression} as \$__key => \$__value) {
	\$__consumeVariable = is_string(\$__key) ? \$__key : \$__value;
	\$\$__consumeVariable = is_string(\$__key) ? \$__env->getConsumableComponentData(\$__key, \$__value) : \$__env->getConsumableComponentData(\$__value);
} ?>";
	}

	/**
	 * Compile a class component opening.
	 */
	public static function compileClassComponentOpening(
		string $component,
		string $alias,
		string $data,
		string $hash
	): string {
		return implode("\n", [
			'<?php if (isset($component)) { $__componentOriginal' . $hash . ' = $component; } ?>',
			'<?php if (isset($attributes)) { $__attributesOriginal' . $hash . ' = $attributes; } ?>',
			'<?php $component = ' . $component . '::resolve(' . ($data ?: '[]') . ' + (isset($attributes) && $attributes instanceof MVPS\Lumis\Framework\View\ComponentAttributeBag ? $attributes->all() : [])); ?>',
			'<?php $component->withName(' . $alias . '); ?>',
			'<?php if ($component->shouldRender()): ?>',
			'<?php $__env->startComponent($component->resolveView(), $component->data()); ?>',
		]);
	}

	/**
	 * Compile the component statements into valid PHP.
	 */
	protected function compileComponent(string|null $expression = null): string
	{
		if (is_null($expression)) {
			$expression = (string) $expression;
		}

		[$component, $alias, $data] = str_contains($expression, ',')
			? array_map('trim', explode(',', trim($expression, '()'), 3)) + ['', '', '']
			: [trim($expression, '()'), '', ''];

		$component = trim($component, '\'"');

		$hash = static::newComponentHash(
			$component === AnonymousComponent::class ? $component . ':' . trim($alias, '\'"') : $component
		);

		if (Str::contains($component, ['::class', '\\'])) {
			return static::compileClassComponentOpening($component, $alias, $data, $hash);
		}

		return "<?php \$__env->startComponent{$expression}; ?>";
	}

	/**
	 * Compile the component-first statements into valid PHP.
	 */
	protected function compileComponentFirst(string|null $expression = null): string
	{
		return "<?php \$__env->startComponentFirst{$expression}; ?>";
	}

	/**
	 * Compile the end-component statements into valid PHP.
	 */
	protected function compileEndComponent(): string
	{
		return '<?php echo $__env->renderComponent(); ?>';
	}

	/**
	 * Compile the end-component statements into valid PHP.
	 */
	public function compileEndComponentClass(): string
	{
		$hash = array_pop(static::$componentHashStack);

		return $this->compileEndComponent() . "\n" . implode("\n", [
			'<?php endif; ?>',
			'<?php if (isset($__attributesOriginal' . $hash . ')): ?>',
			'<?php $attributes = $__attributesOriginal' . $hash . '; ?>',
			'<?php unset($__attributesOriginal' . $hash . '); ?>',
			'<?php endif; ?>',
			'<?php if (isset($__componentOriginal' . $hash . ')): ?>',
			'<?php $component = $__componentOriginal' . $hash . '; ?>',
			'<?php unset($__componentOriginal' . $hash . '); ?>',
			'<?php endif; ?>',
		]);
	}

	/**
	 * Compile the end-component-first statements into valid PHP.
	 */
	protected function compileEndComponentFirst(): string
	{
		return $this->compileEndComponent();
	}

	/**
	 * Compile the end-slot statements into valid PHP.
	 */
	protected function compileEndSlot(): string
	{
		return '<?php $__env->endSlot(); ?>';
	}

	/**
	 * Compile the prop statement into valid PHP.
	 */
	protected function compileProps(string|null $expression = null): string
	{
		return "<?php \$attributes ??= new \\MVPS\\Lumis\\Framework\\View\\ComponentAttributeBag;

\$__newAttributes = [];
\$__propNames = \MVPS\Lumis\Framework\View\ComponentAttributeBag::extractPropNames({$expression});

foreach (\$attributes->all() as \$__key => \$__value) {
	if (in_array(\$__key, \$__propNames)) {
		\$\$__key = \$\$__key ?? \$__value;
	} else {
		\$__newAttributes[\$__key] = \$__value;
	}
}

\$attributes = new \MVPS\Lumis\Framework\View\ComponentAttributeBag(\$__newAttributes);

unset(\$__propNames);
unset(\$__newAttributes);

foreach (array_filter({$expression}, 'is_string', ARRAY_FILTER_USE_KEY) as \$__key => \$__value) {
	\$\$__key = \$\$__key ?? \$__value;
}

\$__defined_vars = get_defined_vars();

foreach (\$attributes->all() as \$__key => \$__value) {
	if (array_key_exists(\$__key, \$__defined_vars)) unset(\$\$__key);
}

unset(\$__defined_vars); ?>";
	}

	/**
	 * Compile the slot statements into valid PHP.
	 */
	protected function compileSlot(string|null $expression = null): string
	{
		return "<?php \$__env->slot{$expression}; ?>";
	}

	/**
	 * Get a new component hash for a component name.
	 */
	public static function newComponentHash(string $component): string
	{
		static::$componentHashStack[] = $hash = hash('xxh128', $component);

		return $hash;
	}

	/**
	 * Sanitize the given component attribute value.
	 */
	public static function sanitizeComponentAttribute(mixed $value): mixed
	{
		if ($value instanceof CanBeEscapedWhenCastToString) {
			return $value->escapeWhenCastingToString();
		}

		return is_string($value) ||
			(is_object($value) && ! $value instanceof ComponentAttributeBag && method_exists($value, '__toString'))
				? e($value)
				: $value;
	}
}
