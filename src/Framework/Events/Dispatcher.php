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

use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Container\Container as ContainerContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher as DispatcherContract;

class Dispatcher extends IlluminateEventDispatcher implements DispatcherContract
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * Create a new event dispatcher instance.
	 */
	public function __construct(ContainerContract|null $container = null)
	{
		$this->container = $container ?: new Container;
	}
}
