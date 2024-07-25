<?php

namespace MVPS\Lumis\Framework\Support;

use MVPS\Lumis\Framework\Collections\Collection;

class CreativityCatalyzer
{
	/**
	 * Returns a collection of inspiring quotes for the Lumis application.
	 * These quotes can be used to motivate and inspire users or team members.
	 */
	public static function catalyzers(): Collection
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
	 * Randomly selects and formats a catalyzing quote for output.
	 */
	public static function dispense(): string
	{
		return static::catalyzers()
			->map(fn ($quote) => static::formatForConsole($quote))
			->random();
	}

	/**
	 * Formats the given catalyzing quote for a pretty console output.
	 */
	protected static function formatForConsole(string $quote): string
	{
		[$text, $author] = str($quote)->explode('-');

		return sprintf(
			"\n  <options=bold>“ %s ”</>\n  <fg=gray>— %s</>\n",
			trim($text),
			trim($author),
		);
	}
}
