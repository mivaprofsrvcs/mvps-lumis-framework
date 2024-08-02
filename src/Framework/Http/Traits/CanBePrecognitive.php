<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use MVPS\Lumis\Framework\Collections\Collection;

trait CanBePrecognitive
{
	/**
	 * Filter the given array of rules into an array of rules that are
	 * included in precognitive headers.
	 */
	public function filterPrecognitiveRules(array $rules): array
	{
		if (! $this->headerBag->has('Precognition-Validate-Only')) {
			return $rules;
		}

		return Collection::make($rules)
			->only(explode(',', $this->header('Precognition-Validate-Only')))
			->all();
	}

	/**
	 * Determine if the request is attempting to be precognitive.
	 */
	public function isAttemptingPrecognition(): bool
	{
		return $this->header('Precognition') === 'true';
	}

	/**
	 * Determine if the request is precognitive.
	 */
	public function isPrecognitive(): bool
	{
		return $this->attributeBag->get('precognitive', false);
	}
}
