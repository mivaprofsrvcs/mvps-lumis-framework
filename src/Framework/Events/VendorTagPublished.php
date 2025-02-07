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

class VendorTagPublished
{
	/**
	 * The publishable paths registered by the tag.
	 *
	 * @var array
	 */
	public array $paths;

	/**
	 * The vendor tag that was published.
	 *
	 * @var string
	 */
	public string $tag;

	/**
	 * Create a new vendor tag published event instance.
	 */
	public function __construct(string $tag, array $paths)
	{
		$this->tag = $tag;
		$this->paths = $paths;
	}
}
