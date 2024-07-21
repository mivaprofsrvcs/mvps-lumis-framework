<?php

namespace MVPS\Lumis\Framework\View\Traits;

use InvalidArgumentException;

trait ManagesStacks
{
	/**
	 * All of the finished, captured prepend sections.
	 *
	 * @var array
	 */
	protected array $prepends = [];

	/**
	 * All of the finished, captured push sections.
	 *
	 * @var array
	 */
	protected array $pushes = [];

	/**
	 * The stack of in-progress push sections.
	 *
	 * @var array
	 */
	protected array $pushStack = [];

	/**
	 * Prepend content to a given stack.
	 */
	protected function extendPrepend(string $section, string $content): void
	{
		if (! isset($this->prepends[$section])) {
			$this->prepends[$section] = [];
		}

		if (! isset($this->prepends[$section][$this->renderCount])) {
			$this->prepends[$section][$this->renderCount] = $content;
		} else {
			$this->prepends[$section][$this->renderCount] = $content . $this->prepends[$section][$this->renderCount];
		}
	}

	/**
	 * Append content to a given push section.
	 */
	protected function extendPush(string $section, string $content): void
	{
		if (! isset($this->pushes[$section])) {
			$this->pushes[$section] = [];
		}

		if (! isset($this->pushes[$section][$this->renderCount])) {
			$this->pushes[$section][$this->renderCount] = $content;
		} else {
			$this->pushes[$section][$this->renderCount] .= $content;
		}
	}

	/**
	 * Flush all of the stacks.
	 */
	public function flushStacks(): void
	{
		$this->pushes = [];
		$this->prepends = [];
		$this->pushStack = [];
	}

	/**
	 * Start prepending content into a push section.
	 */
	public function startPrepend(string $section, string $content = ''): void
	{
		if ($content === '') {
			if (ob_start()) {
				$this->pushStack[] = $section;
			}
		} else {
			$this->extendPrepend($section, $content);
		}
	}

	/**
	 * Start injecting content into a push section.
	 */
	public function startPush(string $section, string $content = ''): void
	{
		if ($content === '') {
			if (ob_start()) {
				$this->pushStack[] = $section;
			}
		} else {
			$this->extendPush($section, $content);
		}
	}

	/**
	 * Stop prepending content into a push section.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function stopPrepend(): string
	{
		if (empty($this->pushStack)) {
			throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
		}

		return tap(
			array_pop($this->pushStack),
			fn ($last) => $this->extendPrepend($last, ob_get_clean())
		);
	}

	/**
	 * Stop injecting content into a push section.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function stopPush(): string
	{
		if (empty($this->pushStack)) {
			throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
		}

		return tap(
			array_pop($this->pushStack),
			fn ($last) => $this->extendPush($last, ob_get_clean())
		);
	}

	/**
	 * Get the string contents of a push section.
	 */
	public function yieldPushContent(string $section, string $default = ''): string
	{
		if (! isset($this->pushes[$section]) && ! isset($this->prepends[$section])) {
			return $default;
		}

		$output = '';

		if (isset($this->prepends[$section])) {
			$output .= implode(array_reverse($this->prepends[$section]));
		}

		if (isset($this->pushes[$section])) {
			$output .= implode($this->pushes[$section]);
		}

		return $output;
	}
}
