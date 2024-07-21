<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\ComponentAttributeBag as IlluminateComponentAttributeBag;
use MVPS\Lumis\Framework\Support\HtmlString;

class ComponentAttributeBag extends IlluminateComponentAttributeBag
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function prepends($value): AppendableAttributeValue
	{
		return new AppendableAttributeValue($value);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function __invoke(array $attributeDefaults = []): HtmlString
	{
		return new HtmlString((string) $this->merge($attributeDefaults));
	}
}
