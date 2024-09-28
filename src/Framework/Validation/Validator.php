<?php

namespace MVPS\Lumis\Framework\Validation;

use Closure;
use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Validation\Validator as IlluminateValidator;
use MVPS\Lumis\Framework\Contracts\Translation\Translator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;
use MVPS\Lumis\Framework\Support\Arr;
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
	 * The custom rendering callbacks for stringable objects.
	 *
	 * @var array
	 */
	protected array $stringableHandlers = [];

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
		parent::__construct($translator, $data, $rules, $messages, $attributes);
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
