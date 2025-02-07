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

namespace MVPS\Lumis\Framework\Validation\Traits;

use Illuminate\Contracts\Validation\Factory;
use MVPS\Lumis\Framework\Contracts\Validation\Validator;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Validation\Exceptions\ValidationException;

trait ValidatesRequests
{
	/**
	 * Get a validation factory instance.
	 */
	protected function getValidationFactory(): Factory
	{
		return app(Factory::class);
	}

	/**
	 * Validate the given request with the given rules.
	 *
	 * @throws \MVPS\Lumis\Framework\Validation\Exceptions\ValidationException
	 */
	public function validate(Request $request, array $rules, array $messages = [], array $attributes = []): array
	{
		$validator = $this->getValidationFactory()
			->make($request->all(), $rules, $messages, $attributes);

		// TODO: Implement this with Precognitive implementation
		// if ($request->isPrecognitive()) {
		// 	$validator->after(Precognition::afterValidationHook($request))
		// 		->setRules(
		// 			$request->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
		// 		);
		// }

		return $validator->validate();
	}

	/**
	 * Run the validation routine against the given validator.
	 *
	 * @throws \MVPS\Lumis\Framework\Validation\Exceptions\ValidationException
	 */
	public function validateWith(Validator|array $validator, Request|null $request = null): array
	{
		$request = $request ?: request();

		if (is_array($validator)) {
			$validator = $this->getValidationFactory()->make($request->all(), $validator);
		}

		// TODO: Implement this with Precognitive implementation
		// if ($request->isPrecognitive()) {
		// 	$validator->after(Precognition::afterValidationHook($request))
		// 		->setRules(
		// 			$request->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
		// 		);
		// }

		return $validator->validate();
	}

	/**
	 * Validate the given request with the given rules.
	 *
	 * @throws \MVPS\Lumis\Framework\Validation\Exceptions\ValidationException
	 */
	public function validateWithBag(
		string $errorBag,
		Request $request,
		array $rules,
		array $messages = [],
		array $attributes = []
	): array {
		try {
			return $this->validate($request, $rules, $messages, $attributes);
		} catch (ValidationException $e) {
			$e->errorBag = $errorBag;

			throw $e;
		}
	}
}
