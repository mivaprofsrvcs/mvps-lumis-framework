<?php

namespace MVPS\Lumis\Framework\Validation;

use Illuminate\Validation\Validator as IlluminateValidator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Validation\Exceptions\ValidationException;
use MVPS\Lumis\Framework\Validation\Traits\InteractsWithMessages;

class Validator extends IlluminateValidator implements ValidatorContract
{
	use InteractsWithMessages;

	/**
	 * {@inheritdoc}
	 */
	protected $exception = ValidationException::class;

	/**
	 * {@inheritdoc}
	 *
	 * @internal Lumis does not currently support a Translator implementation.
	 */
	protected $translator;

	/**
	 * Create a new validator instance.
	 */
	public function __construct(array $data, array $rules, array $messages = [], array $attributes = [])
	{
		$this->dotPlaceholder = Str::random();

		$this->initialRules = $rules;
		$this->customMessages = $messages;
		$this->data = $this->parseData($data);
		$this->customAttributes = $attributes;

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
