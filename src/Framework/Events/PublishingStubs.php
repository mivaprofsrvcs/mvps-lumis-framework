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

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Events\Traits\Dispatchable;

class PublishingStubs
{
	use Dispatchable;

	/**
	 * The stubs being published.
	 *
	 * @var array
	 */
	public array $stubs = [];

	/**
	 * Create a new publishing stubs event instance.
	 */
	public function __construct(array $stubs)
	{
		$this->stubs = $stubs;
	}

	/**
	 * Add a new stub to be published.
	 */
	public function add(string $path, string $name): static
	{
		$this->stubs[$path] = $name;

		return $this;
	}
}
