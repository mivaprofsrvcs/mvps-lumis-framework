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

namespace MVPS\Lumis\Framework\Exceptions\Console;

use InvalidArgumentException;
use RuntimeException;

class Color
{
	/**
	 * ANSI code for background color.
	 *
	 * @var int
	 */
	public const BACKGROUND = 48;

	/**
	 * Regular expression for 256-color codes.
	 *
	 * @var string
	 */
	public const COLOR256_REGEXP = '~^(bg_)?color_(\d{1,3})$~';

	/**
	 * ANSI code for foreground color.
	 *
	 * @var int
	 */
	public const FOREGROUND = 38;

	/**
	 * ANSI code to reset style.
	 *
	 * @var int
	 */
	public const RESET_STYLE = 0;

	/**
	 * Predefined styles and colors.
	 *
	 * @var array
	 */
	protected const STYLES = [
		'none' => null,
		'bold' => '1',
		'dark' => '2',
		'italic' => '3',
		'underline' => '4',
		'blink' => '5',
		'reverse' => '7',
		'concealed' => '8',

		'default' => '39',
		'black' => '30',
		'red' => '31',
		'green' => '32',
		'yellow' => '33',
		'blue' => '34',
		'magenta' => '35',
		'cyan' => '36',
		'light_gray' => '37',

		'dark_gray' => '90',
		'light_red' => '91',
		'light_green' => '92',
		'light_yellow' => '93',
		'light_blue' => '94',
		'light_magenta' => '95',
		'light_cyan' => '96',
		'white' => '97',

		'bg_default' => '49',
		'bg_black' => '40',
		'bg_red' => '41',
		'bg_green' => '42',
		'bg_yellow' => '43',
		'bg_blue' => '44',
		'bg_magenta' => '45',
		'bg_cyan' => '46',
		'bg_light_gray' => '47',

		'bg_dark_gray' => '100',
		'bg_light_red' => '101',
		'bg_light_green' => '102',
		'bg_light_yellow' => '103',
		'bg_light_blue' => '104',
		'bg_light_magenta' => '105',
		'bg_light_cyan' => '106',
		'bg_white' => '107',
	];

	/**
	 * Determines if styles should be forcibly applied.
	 *
	 * @var bool
	 */
	protected bool $forceStyle = false;

	/**
	 * Custom themes mapping.
	 *
	 * @var array
	 */
	protected array $themes = [];

	/**
	 * Add a new theme with specified styles.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \MVPS\Lumis\Framework\Exceptions\Console\InvalidStyleException
	 */
	public function addTheme(string $name, array|string $styles): void
	{
		if (is_string($styles)) {
			$styles = [$styles];
		}
		if (! is_array($styles)) {
			throw new InvalidArgumentException('Style must be string or array.');
		}

		foreach ($styles as $style) {
			if (! $this->isValidStyle($style)) {
				throw new InvalidStyleException($style);
			}
		}

		$this->themes[$name] = $styles;
	}

	/**
	 * Check if 256 colors are supported.
	 */
	public function are256ColorsSupported(): bool
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			return function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT);
		}

		return strpos((string) getenv('TERM'), '256color') !== false;
	}

	/**
	 * Apply styles to the given text.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function apply(array|string $style, string $text): string
	{
		if (! $this->isStyleForced() && ! $this->isSupported()) {
			return $text;
		}

		if (is_string($style)) {
			$style = [$style];
		}
		if (! is_array($style)) {
			throw new InvalidArgumentException('Style must be a string or array.');
		}

		$sequences = [];

		foreach ($style as $s) {
			if (isset($this->themes[$s])) {
				$sequences = array_merge($sequences, $this->themeSequence($s));
			} elseif ($this->isValidStyle($s)) {
				$sequences[] = $this->styleSequence($s);
			} else {
				throw new RuntimeException(sprintf(
					'This should not happen, please open an issue on the Lumis Framework repository: %s',
					'https://github.com/mivaprofsrvcs/mvps-lumis-framework/issues/new'
				));
			}
		}

		$sequences = array_filter($sequences, fn ($val) => ! is_null($val));

		if (empty($sequences)) {
			return $text;
		}

		return $this->escSequence(implode(';', $sequences)) . $text . $this->escSequence(self::RESET_STYLE);
	}

	/**
	 * Generate ANSI escape sequence for a given value.
	 */
	public function escSequence(string|int $value): string
	{
		return "\033[{$value}m";
	}

	/**
	 * Get all possible styles.
	 */
	public function getPossibleStyles(): array
	{
		return array_keys(self::STYLES);
	}

	/**
	 * Get all defined themes.
	 */
	public function getThemes(): array
	{
		return $this->themes;
	}

	/**
	 * Check if a theme exists.
	 */
	public function hasTheme(string $name): bool
	{
		return isset($this->themes[$name]);
	}

	/**
	 * Check if styles are forced.
	 */
	public function isStyleForced(): bool
	{
		return $this->forceStyle;
	}

	/**
	 * Check if ANSI support is available.
	 */
	public function isSupported(): bool
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
		}

		return function_exists('posix_isatty') && @posix_isatty(STDOUT);
	}

	/**
	 * Validate if a given style is valid.
	 */
	public function isValidStyle(string $style): bool
	{
		return array_key_exists($style, self::STYLES) || preg_match(self::COLOR256_REGEXP, $style);
	}

	/**
	 * Remove a theme by its name.
	 */
	public function removeTheme(string $name): void
	{
		unset($this->themes[$name]);
	}

	/**
	 * Set if styles should be forced.
	 */
	public function setForceStyle(bool $forceStyle): void
	{
		$this->forceStyle = $forceStyle;
	}

	/**
	 * Set the provided themes.
	 */
	public function setThemes(array $themes): void
	{
		$this->themes = [];

		foreach ($themes as $name => $styles) {
			$this->addTheme($name, $styles);
		}
	}

	/**
	 * Generate ANSI escape sequence for a given style.
	 */
	public function styleSequence(string $style): string|null
	{
		if (array_key_exists($style, self::STYLES)) {
			return self::STYLES[$style];
		}

		if (! $this->are256ColorsSupported()) {
			return null;
		}

		preg_match(self::COLOR256_REGEXP, $style, $matches);

		$type = $matches[1] === 'bg_' ? self::BACKGROUND : self::FOREGROUND;

		$value = $matches[2];

		return $type . ';5;' . $value;
	}

	/**
	 * Get ANSI escape sequences for a theme.
	 */
	public function themeSequence(string $name): array
	{
		$sequences = [];

		foreach ($this->themes[$name] as $style) {
			$sequences[] = $this->styleSequence($style);
		}

		return $sequences;
	}
}
