<?php

namespace MVPS\Lumis\Framework\Validation;

use Closure;
use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Validation\Validator as IlluminateValidator;
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
	 * {@inheritdoc}
	 *
	 * @see \Illuminate\Validation\Concerns\FormatsMessages::getAttributeFromTranslations()
	 */
	#[\Override]
	protected function getAttributeFromTranslations($name)
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \Illuminate\Validation\Concerns\FormatsMessages::getDisplayableValue()
	 */
	#[\Override]
	public function getDisplayableValue($attribute, $value)
	{
		if (isset($this->customValues[$attribute][$value])) {
			return $this->customValues[$attribute][$value];
		}

		if (is_array($value)) {
			return 'array';
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		if (is_null($value)) {
			return 'empty';
		}

		return (string) $value;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \Illuminate\Validation\Concerns\FormatsMessages::getMessage()
	 */
	#[\Override]
	protected function getMessage($attribute, $rule)
	{
		$attributeWithPlaceholders = $attribute;

		$attribute = $this->replacePlaceholderInString($attribute);

		$inlineMessage = $this->getInlineMessage($attribute, $rule);

		if (! is_null($inlineMessage)) {
			return $inlineMessage;
		}

		// For "size" validation rules, we need to retrieve the specific error
		// message based on the attribute type being validated. Attributes like
		// numbers, files, and strings each require different error messages.
		if (in_array($rule, $this->sizeRules)) {
			return $this->getSizeMessage($attributeWithPlaceholders, $rule);
		}

		// If no special messages apply for this rule, we will just pull the
		// default messages out of the validation messages list.
		$lowerRule = Str::snake($rule);

		$key = $lowerRule;

		$value = $this->validationMessages[$key] ?? $key;

		if ($value !== $key) {
			return $value;
		}

		return $this->getFromLocalArray($attribute, $lowerRule, $this->fallbackMessages) ?: $key;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see \Illuminate\Validation\Concerns\FormatsMessages::getSizeMessage()
	 */
	#[\Override]
	protected function getSizeMessage($attribute, $rule)
	{
		$lowerRule = Str::snake($rule);

		$type = $this->getAttributeType($attribute);

		return $this->validationMessages[$lowerRule][$type] ?? '';
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
