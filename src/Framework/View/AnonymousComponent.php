<?php

namespace MVPS\Lumis\Framework\View;

class AnonymousComponent extends Component
{
	/**
	 * The component data.
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * The component view.
	 *
	 * @var string
	 */
	protected string $view;

	/**
	 * Create a new anonymous component instance.
	 */
	public function __construct(string $view, array $data)
	{
		$this->view = $view;
		$this->data = $data;
	}

	/**
	 * Get the data that should be supplied to the view.
	 */
	public function data(): array
	{
		$this->attributes = $this->attributes ?: $this->newAttributeBag();

		return array_merge(
			($this->data['attributes'] ?? null)?->getAttributes() ?: [],
			$this->attributes->getAttributes(),
			$this->data,
			['attributes' => $this->attributes]
		);
	}

	/**
	 * Get the view / view contents that represent the component.
	 */
	public function render(): string
	{
		return $this->view;
	}
}
