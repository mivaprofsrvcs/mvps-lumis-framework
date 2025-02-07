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

use Illuminate\View\View as IlluminateView;
use MVPS\Lumis\Framework\Contracts\Support\Htmlable;
use MVPS\Lumis\Framework\Contracts\View\Engine;
use MVPS\Lumis\Framework\Contracts\View\View as ViewContract;

class View extends IlluminateView implements Htmlable, ViewContract
{
	/**
	 * The engine implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Engine
	 */
	protected $engine;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\View\Factory
	 */
	protected $factory;

	/**
	 * Create a new view instance.
	 */
	public function __construct(Factory $factory, Engine $engine, string $view, string $path, mixed $data = [])
	{
		parent::__construct($factory, $engine, $view, $path, $data);
	}
}
