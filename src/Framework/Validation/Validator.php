<?php

namespace MVPS\Lumis\Framework\Validation;

use Closure;
use Illuminate\Validation\Validator as IlluminateValidator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Support\Traits\ReflectsClosures;
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
	 * @internal Lumis does not currently support a Translator implementation.
	 */
	protected $translator;

	/**
	 * The list of validation messages.
	 *
	 * @var array
	 */
	protected array $validationMessages;

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

		$this->validationMessages = (new ValidationMessages)();

		$this->setRules($rules);
	}

	/**
	 * Get the translated validation message for the given key.
	 */
	public function getValidationMessage(string $key, array $replace = []): string|array
	{
		$line = $this->validationMessages[$key] ?? null;

		return $this->withReplacements((string) ($line ?: $key), $replace);
	}

	/**
	 * Add a handler to be executed in order to format a given class
	 * to a string during message replacements.
	 */
	public function stringable(callable|string $class, callable|null $handler = null): void
	{
		if ($class instanceof Closure) {
			[$class, $handler] = [$this->firstClosureParameterType($class), $class];
		}

		$this->stringableHandlers[$class] = $handler;
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

	/**
	 * Make the placeholder replacements on a message line.
	 */
	protected function withReplacements(string $line, array $replace): string
	{
		if (empty($replace)) {
			return $line;
		}

		$shouldReplace = [];

		foreach ($replace as $key => $value) {
			if ($value instanceof Closure) {
				$line = preg_replace_callback(
					'/<' . $key . '>(.*?)<\/' . $key . '>/',
					fn ($args) => $value($args[1]),
					$line
				);

				continue;
			}

			if (is_object($value) && isset($this->stringableHandlers[get_class($value)])) {
				$value = call_user_func($this->stringableHandlers[get_class($value)], $value);
			}

			$shouldReplace[':' . Str::ucfirst($key)] = Str::ucfirst($value ?? '');
			$shouldReplace[':' . Str::upper($key)] = Str::upper($value ?? '');
			$shouldReplace[':' . $key] = $value;
		}

		return strtr($line, $shouldReplace);
	}
}
