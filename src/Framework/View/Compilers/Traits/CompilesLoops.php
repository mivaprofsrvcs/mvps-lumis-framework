<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use MVPS\Lumis\Framework\Contracts\View\ViewCompilationException;

trait CompilesLoops
{
	/**
	 * Counter to keep track of nested forelse statements.
	 *
	 * @var int
	 */
	protected int $forElseCounter = 0;

	/**
	 * Compile the break statements into valid PHP.
	 */
	protected function compileBreak(string|null $expression = null): string
	{
		if ($expression) {
			preg_match('/\(\s*(-?\d+)\s*\)$/', $expression, $matches);

			return $matches
				? '<?php break ' . max(1, $matches[1]) . '; ?>'
				: "<?php if{$expression} break; ?>";
		}

		return '<?php break; ?>';
	}

	/**
	 * Compile the continue statements into valid PHP.
	 */
	protected function compileContinue(string|null $expression = null): string
	{
		if ($expression) {
			preg_match('/\(\s*(-?\d+)\s*\)$/', $expression, $matches);

			return $matches
				? '<?php continue ' . max(1, $matches[1]) . '; ?>'
				: "<?php if{$expression} continue; ?>";
		}

		return '<?php continue; ?>';
	}

	/**
	 * Compile the for-else-empty and empty statements into valid PHP.
	 */
	protected function compileEmpty(string|null $expression = null): string
	{
		if ($expression) {
			return "<?php if(empty{$expression}): ?>";
		}

		$empty = '$__empty_' . $this->forElseCounter--;

		return "<?php endforeach; \$__env->popLoop(); \$loop = \$__env->getLastLoop(); if ({$empty}): ?>";
	}

	/**
	 * Compile the end-empty statements into valid PHP.
	 */
	protected function compileEndEmpty(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-for statements into valid PHP.
	 */
	protected function compileEndfor(): string
	{
		return '<?php endfor; ?>';
	}

	/**
	 * Compile the end-for-each statements into valid PHP.
	 */
	protected function compileEndforeach(): string
	{
		return '<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>';
	}

	/**
	 * Compile the end-for-else statements into valid PHP.
	 */
	protected function compileEndforelse(): string
	{
		return '<?php endif; ?>';
	}

	/**
	 * Compile the end-while statements into valid PHP.
	 */
	protected function compileEndwhile(): string
	{
		return '<?php endwhile; ?>';
	}

	/**
	 * Compile the for statements into valid PHP.
	 */
	protected function compileFor(string|null $expression = null): string
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile the for-each statements into valid PHP.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\View\ViewCompilationException
	 */
	protected function compileForeach(string|null $expression = null): string
	{
		preg_match('/\( *(.+) +as +(.*)\)$/is', $expression ?? '', $matches);

		if (count($matches) === 0) {
			throw new ViewCompilationException('Malformed @foreach statement.');
		}

		$iteratee = trim($matches[1]);

		$iteration = trim($matches[2]);

		$initLoop = "\$__currentLoopData = {$iteratee}; \$__env->addLoop(\$__currentLoopData);";

		$iterateLoop = '$__env->incrementLoopIndices(); $loop = $__env->getLastLoop();';

		return "<?php {$initLoop} foreach(\$__currentLoopData as {$iteration}): {$iterateLoop} ?>";
	}

	/**
	 * Compile the for-else statements into valid PHP.
	 *
	 * @throws \MVPS\Lumis\Framework\Contracts\View\ViewCompilationException
	 */
	protected function compileForelse(string|null $expression = null): string
	{
		$empty = '$__empty_' . ++$this->forElseCounter;

		preg_match('/\( *(.+) +as +(.+)\)$/is', $expression ?? '', $matches);

		if (count($matches) === 0) {
			throw new ViewCompilationException('Malformed @forelse statement.');
		}

		$iteratee = trim($matches[1]);

		$iteration = trim($matches[2]);

		$initLoop = "\$__currentLoopData = {$iteratee}; \$__env->addLoop(\$__currentLoopData);";

		$iterateLoop = '$__env->incrementLoopIndices(); $loop = $__env->getLastLoop();';

		return "<?php {$empty} = true; {$initLoop} foreach(\$__currentLoopData as {$iteration}): {$iterateLoop} {$empty} = false; ?>";
	}

	/**
	 * Compile the while statements into valid PHP.
	 */
	protected function compileWhile(string|null $expression = null): string
	{
		return "<?php while{$expression}: ?>";
	}
}
