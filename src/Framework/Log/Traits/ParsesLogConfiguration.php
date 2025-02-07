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

namespace MVPS\Lumis\Framework\Log\Traits;

use InvalidArgumentException;
use Monolog\Level;

trait ParsesLogConfiguration
{
	/**
	 * The Log levels.
	 *
	 * @var array
	 */
	protected array $levels = [
		'debug' => Level::Debug,
		'info' => Level::Info,
		'notice' => Level::Notice,
		'warning' => Level::Warning,
		'error' => Level::Error,
		'critical' => Level::Critical,
		'alert' => Level::Alert,
		'emergency' => Level::Emergency,
	];

	/**
	 * Parse the action level from the given configuration.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function actionLevel(array $config): int
	{
		$level = $config['action_level'] ?? 'debug';

		if (isset($this->levels[$level])) {
			return $this->levels[$level];
		}

		throw new InvalidArgumentException('Invalid log action level.');
	}

	/**
	 * Get fallback log channel name.
	 */
	abstract protected function getFallbackChannelName(): string;

	/**
	 * Parse the string level into a Monolog constant.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function level(array $config): Level|int
	{
		$level = $config['level'] ?? 'debug';

		if (isset($this->levels[$level])) {
			return $this->levels[$level];
		}

		throw new InvalidArgumentException('Invalid log level.');
	}

	/**
	 * Extract the log channel from the given configuration.
	 */
	protected function parseChannel(array $config): string
	{
		return $config['name'] ?? $this->getFallbackChannelName();
	}
}
