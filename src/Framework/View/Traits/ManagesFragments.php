<?php

namespace MVPS\Lumis\Framework\View\Traits;

use InvalidArgumentException;

trait ManagesFragments
{
	/**
	 * All of the captured, rendered fragments.
	 *
	 * @var array
	 */
	protected array $fragments = [];

	/**
	 * The stack of in-progress fragment renders.
	 *
	 * @var array
	 */
	protected array $fragmentStack = [];

	/**
	 * Flush all of the fragments.
	 */
	public function flushFragments(): void
	{
		$this->fragments = [];
		$this->fragmentStack = [];
	}

	/**
	 * Get the contents of a fragment.
	 */
	public function getFragment(string $name, string|null $default = null): mixed
	{
		return $this->getFragments()[$name] ?? $default;
	}

	/**
	 * Get the entire array of rendered fragments.
	 */
	public function getFragments(): array
	{
		return $this->fragments;
	}

	/**
	 * Start injecting content into a fragment.
	 */
	public function startFragment(string $fragment): void
	{
		if (ob_start()) {
			$this->fragmentStack[] = $fragment;
		}
	}

	/**
	 * Stop injecting content into a fragment.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function stopFragment(): string
	{
		if (empty($this->fragmentStack)) {
			throw new InvalidArgumentException('Cannot end a fragment without first starting one.');
		}

		$last = array_pop($this->fragmentStack);

		$this->fragments[$last] = ob_get_clean();

		return $this->fragments[$last];
	}
}
