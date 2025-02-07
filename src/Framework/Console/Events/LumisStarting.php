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

namespace MVPS\Lumis\Framework\Console\Events;

use MVPS\Lumis\Framework\Console\Application as ConsoleApplication;

class LumisStarting
{
	/**
	 * The Lumis console application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Console\Application
	 */
	public ConsoleApplication $lumis;

	/**
	 * Create a new Lumis starting event instance.
	 */
	public function __construct(ConsoleApplication $lumis)
	{
		$this->lumis = $lumis;
	}
}
