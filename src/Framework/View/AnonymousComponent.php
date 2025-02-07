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
