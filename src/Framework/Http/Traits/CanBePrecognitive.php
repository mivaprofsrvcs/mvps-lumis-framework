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
