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

namespace MVPS\Lumis\Framework\Support;

use MVPS\Lumis\Framework\Collections\Collection;

class Stimulate
{
	/**
	 * Returns a collection of inspiring quotes for the Lumis application.
	 * These quotes can be used to motivate and inspire users or team members.
	 */
	public static function stimulants(): Collection
	{
		return Collection::make([
			'Lumis: Illuminate your ideas.',
			'Code with purpose, build with Lumis.',
			'Unleash your creativity with Lumis.',
			'Lumis: Your vision, our platform.',
			'Simplify, innovate, Lumis.',
			'Lumis empowers you to build extraordinary applications.',
			'With Lumis, the possibilities are endless.',
			'Dream big, build bigger with Lumis.',
			'Lumis: Your partner in development excellence.',
			'Ignite your passion for coding with Lumis.',
			'Lumis: Accelerate your development process.',
			'Achieve more with less, choose Lumis.',
			'Optimize your workflow with Lumis.',
			'Lumis: Delivering results, faster.',
			'Efficiency meets innovation in Lumis.',
		]);
	}

	/**
	 * Randomly selects and formats a stimulating quote for output.
	 */
	public static function dispense(): string
	{
		return static::stimulants()
			->map(fn ($quote) => static::formatForConsole($quote))
			->random();
	}

	/**
	 * Formats the given stimulating quote for a pretty console output.
	 */
	protected static function formatForConsole(string $quote): string
	{
		return sprintf("\n  <options=bold>“ %s ”</>\n", trim($quote));
	}
}
