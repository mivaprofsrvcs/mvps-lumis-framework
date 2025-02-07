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

namespace MVPS\Lumis\Framework\Validation;

use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Validation\Validator as IlluminateValidator;
use MVPS\Lumis\Framework\Contracts\Translation\Translator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Validation\Exceptions\ValidationException;

class Validator extends IlluminateValidator implements ValidatorContract
{
	use ReflectsClosures;

	/**
	 * {@inheritdoc}
	 */
	protected $exception = ValidationException::class;

	/**
	 * {@inheritdoc}
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Translation\Translator
	 */
	protected $translator;

	/**
	 * Create a new validator instance.
	 */
	public function __construct(
		Translator $translator,
		array $data,
		array $rules,
		array $messages = [],
		array $attributes = []
	) {
		$this->dotPlaceholder = Str::random();

		$this->initialRules = $rules;
		$this->translator = $translator;
		$this->customMessages = $messages;
		$this->customAttributes = $attributes;
		$this->data = $this->parseData($data);

		$this->setRules($rules);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \MVPS\Lumis\Framework\Validation\Exceptions\ValidationException
	 */
	#[\Override]
	public function validateWithBag(string $errorBag)
	{
		try {
			return $this->validate();
		} catch (ValidationException $e) {
			$e->errorBag = $errorBag;

			throw $e;
		}
	}
}
