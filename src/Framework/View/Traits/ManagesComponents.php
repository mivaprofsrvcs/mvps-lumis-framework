<?php

namespace MVPS\Lumis\Framework\View\Traits;

use Closure;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Contracts\Support\Htmlable;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\View\ComponentSlot;

trait ManagesComponents
{
	/**
	 * The original data passed to the component.
	 *
	 * @var array
	 */
	protected array $componentData = [];

	/**
	 * The components being rendered.
	 *
	 * @var array
	 */
	protected array $componentStack = [];

	/**
	 * The component data for the component that is currently being rendered.
	 *
	 * @var array
	 */
	protected array $currentComponentData = [];

	/**
	 * The slot contents for the component.
	 *
	 * @var array
	 */
	protected array $slots = [];

	/**
	 * The names of the slots being rendered.
	 *
	 * @var array
	 */
	protected array $slotStack = [];

	/**
	 * Get the data for the given component.
	 */
	protected function componentData(): array
	{
		$defaultSlot = new ComponentSlot(trim(ob_get_clean()));

		$slots = array_merge(
			['__default' => $defaultSlot],
			$this->slots[count($this->componentStack)]
		);

		return array_merge(
			$this->componentData[count($this->componentStack)],
			['slot' => $defaultSlot],
			$this->slots[count($this->componentStack)],
			['__lumis_slots' => $slots]
		);
	}

	/**
	 * Get the index for the current component.
	 */
	protected function currentComponent(): int
	{
		return count($this->componentStack) - 1;
	}

	/**
	 * Save the slot content for rendering.
	 */
	public function endSlot(): void
	{
		last($this->componentStack);

		$currentSlot = array_pop(
			$this->slotStack[$this->currentComponent()]
		);

		[$currentName, $currentAttributes] = $currentSlot;

		$this->slots[$this->currentComponent()][$currentName] = new ComponentSlot(
			trim(ob_get_clean()),
			$currentAttributes
		);
	}

	/**
	 * Flush all of the component state.
	 */
	protected function flushComponents(): void
	{
		$this->componentStack = [];
		$this->componentData = [];
		$this->currentComponentData = [];
	}

	/**
	 * Get an item from the component data that exists above the current component.
	 */
	public function getConsumableComponentData(string $key, mixed $default = null): mixed
	{
		if (array_key_exists($key, $this->currentComponentData)) {
			return $this->currentComponentData[$key];
		}

		$currentComponent = count($this->componentStack);

		if ($currentComponent === 0) {
			return value($default);
		}

		for ($i = $currentComponent - 1; $i >= 0; $i--) {
			$data = $this->componentData[$i] ?? [];

			if (array_key_exists($key, $data)) {
				return $data[$key];
			}
		}

		return value($default);
	}

	/**
	 * Render the current component.
	 */
	public function renderComponent(): string
	{
		$view = array_pop($this->componentStack);

		$this->currentComponentData = array_merge(
			$previousComponentData = $this->currentComponentData,
			$data = $this->componentData()
		);

		try {
			$view = value($view, $data);

			if ($view instanceof View) {
				return $view->with($data)->render();
			} elseif ($view instanceof Htmlable) {
				return $view->toHtml();
			} else {
				return $this->make($view, $data)->render();
			}
		} finally {
			$this->currentComponentData = $previousComponentData;
		}
	}

	/**
	 * Start the slot rendering process.
	 */
	public function slot(string $name, string|null $content = null, array $attributes = []): void
	{
		if (func_num_args() === 2 || $content !== null) {
			$this->slots[$this->currentComponent()][$name] = $content;
		} elseif (ob_start()) {
			$this->slots[$this->currentComponent()][$name] = '';

			$this->slotStack[$this->currentComponent()][] = [$name, $attributes];
		}
	}

	/**
	 * Start a component rendering process.
	 */
	public function startComponent(View|Htmlable|Closure|string $view, array $data = []): void
	{
		if (ob_start()) {
			$this->componentStack[] = $view;

			$this->componentData[$this->currentComponent()] = $data;

			$this->slots[$this->currentComponent()] = [];
		}
	}

	/**
	 * Get the first view that actually exists from the given list, and start a component.
	 */
	public function startComponentFirst(array $names, array $data = []): void
	{
		$name = Arr::first($names, function ($item) {
			return $this->exists($item);
		});

		$this->startComponent($name, $data);
	}
}
