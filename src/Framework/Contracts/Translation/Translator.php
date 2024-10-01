<?php

namespace MVPS\Lumis\Framework\Contracts\Translation;

use Countable;

interface Translator
{
	/**
	 * Get a translation according to an integer value.
	 */
	public function choice(string $key, Countable|int|float|array $number, array $replace = []): string;

	/**
	 * Get the translation for a given key.
	 *
	 * @return mixed
	 */
	public function get(string $key, array $replace = []);
}
