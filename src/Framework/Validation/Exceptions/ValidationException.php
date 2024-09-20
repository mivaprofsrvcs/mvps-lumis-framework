<?php

namespace MVPS\Lumis\Framework\Validation\Exceptions;

use Illuminate\Validation\ValidationException as IlluminateValidationException;
use MVPS\Lumis\Framework\Contracts\Validation\Validator;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Support\Str;

class ValidationException extends IlluminateValidationException
{
	/**
	 * The recommended response to send to the client.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Response|null
	 */
	public $response;

	/**
	 * The validator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Validation\Validator
	 */
	public $validator;

	public function __construct(Validator $validator, Response|null $response = null, string $errorBag = 'default')
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

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected static function summarize($validator)
	{
		$messages = $validator->errors()->all();

		if (! count($messages) || ! is_string($messages[0])) {
			return 'The given data was invalid.';
		}

		$message = array_shift($messages);
		$count = count($messages);

		if ($count) {
			$message .= sprintf(' (and %s more %s)', $count, Str::plural('error', $count));
		}

		return $message;
	}
}
