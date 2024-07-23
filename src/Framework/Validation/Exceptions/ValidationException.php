<?php

namespace MVPS\Lumis\Framework\Validation\Exceptions;

use Illuminate\Validation\ValidationException as IlluminateValidationException;
use MVPS\Lumis\Framework\Contracts\Validation\Validator;
use MVPS\Lumis\Framework\Http\Response;

class ValidationException extends IlluminateValidationException
{
	/**
	 * The validator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Validation\Validator
	 */
	public $validator;

	/**
	 * The recommended response to send to the client.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Response|null
	 */
	public $response;

	public function __construct(Validator $validator, Response $response = null, string $errorBag = 'default')
	{
		parent::__construct($validator, $response, $errorBag);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return \MVPS\Lumis\Framework\Http\Response|null
	 */
	#[\Override]
	public function getResponse(): Response|null
	{
		return $this->response;
	}
}
