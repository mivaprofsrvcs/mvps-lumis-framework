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

namespace MVPS\Lumis\Framework\Log\Events;

class MessageLogged
{
	/**
	 * The log context.
	 *
	 * @var array
	 */
	public array $context;

	/**
	 * The log "level".
	 *
	 * @var string
	 */
	public string $level;

	/**
	 * The log message.
	 *
	 * @var string
	 */
	public string $message;

	/**
	 * Create a new messaged logged event instance.
	 */
	public function __construct(string $level, string $message, array $context = [])
	{
		$this->level = $level;
		$this->message = $message;
		$this->context = $context;
	}
}
