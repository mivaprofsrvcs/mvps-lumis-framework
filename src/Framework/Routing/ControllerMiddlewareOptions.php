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

namespace MVPS\Lumis\Framework\Routing;

class ControllerMiddlewareOptions
{
	/**
	 * The middleware options.
	 */
	protected array $options;

	/**
	 * Create a new middleware option instance.
	 */
	public function __construct(array &$options)
	{
		$this->options = &$options;
	}

	/**
	 * Set the controller methods the middleware should exclude.
	 */
	public function except(mixed $methods): static
	{
		$this->options['except'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}

	/**
	 * Set the controller methods the middleware should apply to.
	 */
	public function only(mixed $methods): static
	{
		$this->options['only'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}
}
