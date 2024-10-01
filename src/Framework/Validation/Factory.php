<?php

namespace MVPS\Lumis\Framework\Validation;

use Closure;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\PresenceVerifierInterface;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Translation\Translator;
use MVPS\Lumis\Framework\Support\Str;

class Factory implements ValidationFactory
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container|null
	 */
	protected Container|null $container;

	/**
	 * The custom dependent validator extensions.
	 *
	 * @var array<string, \Closure|string>
	 */
	protected array $dependentExtensions = [];

	/**
	 * Indicates that un-validated array keys should be excluded,
	 * even if the parent array was validated.
	 *
	 * @var bool
	 */
	protected bool $excludeUnvalidatedArrayKeys = true;

	/**
	 * The custom validator extensions.
	 *
	 * @var array<string, \Closure|string>
	 */
	protected array $extensions = [];

	/**
	 * The fallback messages for custom rules.
	 *
	 * @var array<string, string>
	 */
	protected array $fallbackMessages = [];

	/**
	 * The custom implicit validator extensions.
	 *
	 * @var array<string, \Closure|string>
	 */
	protected array $implicitExtensions = [];

	/**
	 * The custom validator message replacers.
	 *
	 * @var array<string, \Closure|string>
	 */
	protected array $replacers = [];

	/**
	 * The Validator resolver instance.
	 *
	 * @var \Closure|null
	 */
	protected $resolver = null;

	/**
	 * The Translator implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Translation\Translator
	 */
	protected Translator $translator;

	/**
	 * The Presence Verifier implementation.
	 *
	 * @var \Illuminate\Validation\PresenceVerifierInterface|null
	 */
	protected PresenceVerifierInterface|null $verifier = null;

	/**
	 * Create a new validator factory instance.
	 */
	public function __construct(Translator $translator, Container|null $container = null)
	{
		$this->container = $container;
		$this->translator = $translator;
	}

	/**
	 * Add the extensions to a validator instance.
	 */
	protected function addExtensions(Validator $validator): void
	{
		$validator->addExtensions($this->extensions);

		$validator->addImplicitExtensions($this->implicitExtensions);

		$validator->addDependentExtensions($this->dependentExtensions);

		$validator->addReplacers($this->replacers);

		$validator->setFallbackMessages($this->fallbackMessages);
	}

	/**
	 * Indicate that un-validated array keys should be excluded from
	 * the validated data, even if the parent array was validated.
	 */
	public function excludeUnvalidatedArrayKeys(): void
	{
		$this->excludeUnvalidatedArrayKeys = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function extend($rule, $extension, $message = null)
	{
		$this->extensions[$rule] = $extension;

		if ($message) {
			$this->fallbackMessages[Str::snake($rule)] = $message;
		}
	}

	/**
	 * Register a custom dependent validator extension.
	 */
	public function extendDependent(string $rule, Closure|string $extension, string|null $message = null): void
	{
		$this->dependentExtensions[$rule] = $extension;

		if ($message) {
			$this->fallbackMessages[Str::snake($rule)] = $message;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function extendImplicit($rule, $extension, $message = null)
	{
		$this->implicitExtensions[$rule] = $extension;

		if ($message) {
			$this->fallbackMessages[Str::snake($rule)] = $message;
		}
	}

	/**
	 * Get the container instance used by the validation factory.
	 */
	public function getContainer(): Container|null
	{
		return $this->container;
	}

	/**
	 * Get the presence verifier implementation instance.
	 */
	public function getPresenceVerifier(): PresenceVerifierInterface|null
	{
		return $this->verifier;
	}

	/**
	 * Get the Translator implementation.
	 */
	public function getTranslator(): Translator
	{
		return $this->translator;
	}

	/**
	 * Indicate that un-validated array keys should be included
	 * in validated data when the parent array is validated.
	 */
	public function includeUnvalidatedArrayKeys(): void
	{
		$this->excludeUnvalidatedArrayKeys = false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function make(array $data, array $rules, array $messages = [], array $attributes = [])
	{
		$validator = $this->resolve($data, $rules, $messages, $attributes);

		if (! is_null($this->verifier)) {
			$validator->setPresenceVerifier($this->verifier);
		}

		if (! is_null($this->container)) {
			$validator->setContainer($this->container);
		}

		$validator->excludeUnvalidatedArrayKeys = $this->excludeUnvalidatedArrayKeys;

		$this->addExtensions($validator);

		return $validator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function replacer($rule, $replacer)
	{
		$this->replacers[$rule] = $replacer;
	}

	/**
	 * Resolve a new validator instance.
	 */
	protected function resolve(array $data, array $rules, array $messages, array $attributes): Validator
	{
		if (is_null($this->resolver)) {
			return new Validator($this->translator, $data, $rules, $messages, $attributes);
		}

		return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $attributes);
	}

	/**
	 * Set the validator instance resolver.
	 */
	public function resolver(Closure $resolver): void
	{
		$this->resolver = $resolver;
	}

	/**
	 * Set the container instance used by the validation factory.
	 */
	public function setContainer(Container $container): static
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Set the presence verifier implementation instance.
	 */
	public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier): void
	{
		$this->verifier = $presenceVerifier;
	}

	/**
	 * Validate the given data against the provided rules.
	 *
	 * @throws \MVPS\Lumis\Framework\Validation\Exceptions\ValidationException
	 */
	public function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
	{
		return $this->make($data, $rules, $messages, $attributes)->validate();
	}
}
