<?php

namespace MVPS\Lumis\Framework\Translation;

class MessageSelector
{
	/**
	 * Select a proper translation string based on the given number.
	 */
	public function choose(string $line, int $number): mixed
	{
		$segments = explode('|', $line);

		$value = $this->extract($segments, $number);

		if (! is_null($value)) {
			return trim($value);
		}

		$segments = $this->stripConditions($segments);

		if (count($segments) === 1 || ! isset($segments[$number])) {
			return $segments[0];
		}

		return $segments[$number];
	}

	/**
	 * Extract a translation string using inline conditions.
	 */
	private function extract(array $segments, int $number): mixed
	{
		foreach ($segments as $part) {
			$line = $this->extractFromString($part, $number);

			if (! is_null($line)) {
				return $line;
			}
		}

		return null;
	}

	/**
	 * Get the translation string if the condition matches.
	 */
	private function extractFromString(string $part, int $number): mixed
	{
		preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);

		if (count($matches) !== 3) {
			return null;
		}

		$condition = $matches[1];

		$value = $matches[2];

		if (str_contains($condition, ',')) {
			[$from, $to] = explode(',', $condition, 2);

			if ($to === '*' && $number >= $from) {
				return $value;
			} elseif ($from === '*' && $number <= $to) {
				return $value;
			} elseif ($number >= $from && $number <= $to) {
				return $value;
			}
		}

		return (int) $condition === $number ? $value : null;
	}

	/**
	 * Strip the inline conditions from each segment, just leaving the text.
	 */
	private function stripConditions(array $segments): array
	{
		return collection($segments)
			->map(fn ($part) => preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part))
			->all();
	}
}
