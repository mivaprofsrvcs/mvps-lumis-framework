<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\ComponentSlot as IlluminateComponentSlot;

class ComponentSlot extends IlluminateComponentSlot
{
	/**
	 * The slot attribute bag.
	 *
	 * @var \MVPS\Lumis\Framework\View\ComponentAttributeBag
	 */
	public $attributes;

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function withAttributes(array $attributes): static
	{
		$this->attributes = new ComponentAttributeBag($attributes);

		return $this;
	}
}
