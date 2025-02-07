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

namespace MVPS\Lumis\Framework\Routing\Controllers;

use Closure;
use MVPS\Lumis\Framework\Support\Arr;

class Middleware
{
	/**
	 * The list of controller methods the middleware should not apply to.
	 *
	 * @var array|null
	 */
	public array|null $except;

	/**
	 * The middleware to be applied.
	 *
	 * @var Closure|string|array
	 */
	public Closure|string|array $middleware;

	/**
	 * The list of controller methods the middleware should apply to.
	 *
	 * @var array|null
	 */
	public array|null $only;

	/**
	 * Create a new controller middleware definition.
	 */
	public function __construct(Closure|string|array $middleware, array|null $only = null, array|null $except = null)
	{
		$this->middleware = $middleware;
		$this->only = $only;
		$this->except = $except;
	}

	/**
	 * Specify the controller methods the middleware should not apply to.
	 */
	public function except(array|string $except): static
	{
		$this->except = Arr::wrap($except);

		return $this;
	}

	/**
	 * Specify the only controller methods the middleware should apply to.
	 */
	public function only(array|string $only): static
	{
		$this->only = Arr::wrap($only);

		return $this;
	}
}
