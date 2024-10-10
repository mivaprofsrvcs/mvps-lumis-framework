<?php

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
	protected function level(array $config): int
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
