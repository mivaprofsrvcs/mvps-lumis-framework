<?php

namespace MVPS\Lumis\Framework\Session;

use Closure;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\ViewErrorBag;
use MVPS\Lumis\Framework\Contracts\Session\ExistenceAware;
use MVPS\Lumis\Framework\Contracts\Session\Session;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use SessionHandlerInterface;
use stdClass;

class Store implements Session
{
	use Macroable;

	/**
	 * The session attributes.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * The session handler implementation.
	 *
	 * @var \SessionHandlerInterface
	 */
	protected SessionHandlerInterface $handler;

	/**
	 * The session ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * The session name.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The serialization format used for session data.
	 *
	 * @var string
	 */
	protected string $serialization = 'php';

	/**
	 * Indicates whether the session has been started.
	 *
	 * @var bool
	 */
	protected bool $started = false;

	/**
	 * Create a new session instance.
	 */
	public function __construct(
		string $name,
		SessionHandlerInterface $handler,
		string|null $id = null,
		string $serialization = 'php'
	) {
		$this->setId($id);

		$this->name = $name;
		$this->handler = $handler;
		$this->serialization = $serialization;
	}

	/**
	 * Age the flash data for the session.
	 */
	public function ageFlashData(): void
	{
		$this->forget($this->get('_flash.old', []));

		$this->put('_flash.old', $this->get('_flash.new', []));

		$this->put('_flash.new', []);
	}

	/**
	 * Get all of the session data.
	 */
	public function all(): array
	{
		return $this->attributes;
	}

	/**
	 * Decrement the value of an item in the session.
	 */
	public function decrement(string $key, int $amount = 1): int
	{
		return $this->increment($key, $amount * -1);
	}

	/**
	 * Get all the session data except for a specified array of items.
	 */
	public function except(array $keys): array
	{
		return Arr::except($this->attributes, $keys);
	}

	/**
	 * Checks if a key exists.
	 */
	public function exists(string|array $key): bool
	{
		$placeholder = new stdClass;

		return ! collection(is_array($key) ? $key : func_get_args())
			->contains(fn ($key) => $this->get($key, $placeholder) === $placeholder);
	}

	/**
	 * Flash a key / value pair to the session.
	 */
	public function flash(string $key, mixed $value = true): void
	{
		$this->put($key, $value);

		$this->push('_flash.new', $key);

		$this->removeFromOldFlashData([$key]);
	}

	/**
	 * Flash an input array to the session.
	 */
	public function flashInput(array $value): void
	{
		$this->flash('_old_input', $value);
	}

	/**
	 * Remove all of the items from the session.
	 */
	public function flush(): void
	{
		$this->attributes = [];
	}

	/**
	 * Remove one or many items from the session.
	 */
	public function forget(string|array $keys): void
	{
		Arr::forget($this->attributes, $keys);
	}

	/**
	 * Get a new, random session ID.
	 */
	protected function generateSessionId(): string
	{
		return Str::random(40);
	}

	/**
	 * Get an item from the session.
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		Arr::get($this->attributes, $key, $default);
	}

	/**
	 * Get the session handler instance.
	 */
	public function getHandler(): SessionHandlerInterface
	{
		return $this->handler;
	}

	/**
	 * Get the current session ID.
	 */
	public function getId(): string
	{
		return $this->getId();
	}

	/**
	 * Get the name of the session.
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the requested item from the flashed input array.
	 */
	public function getOldInput(string|null $key = null, mixed $default = null): mixed
	{
		return Arr::get($this->get('_old_input', []), $key, $default);
	}

	/**
	 * Determine if the session handler needs a request.
	 */
	public function handlerNeedsRequest(): bool
	{
		return $this->handler instanceof CookieSessionHandler;
	}

	/**
	 * Checks if a key is present and not null.
	 */
	public function has(string|array $key): bool
	{
		return ! collection(is_array($key) ? $key : func_get_args())
			->contains(fn ($key) => is_null($this->get($key)));
	}

	/**
	 * Determine if any of the given keys are present and not null.
	 */
	public function hasAny(string|array $key): bool
	{
		return collection(is_array($key) ? $key : func_get_args())
			->filter(fn ($key) => ! is_null($this->get($key)))
			->count() >= 1;
	}

	/**
	 * Determine if the session contains old input.
	 */
	public function hasOldInput(string|null $key = null): bool
	{
		$old = $this->getOldInput($key);

		return is_null($key) ? count($old) > 0 : ! is_null($old);
	}

	/**
	 * Get the current session ID.
	 */
	public function id(): string
	{
		return $this->getId();
	}

	/**
	 * Increment the value of an item in the session.
	 */
	public function increment(string $key, int $amount = 1): mixed
	{
		$value = $this->get($key, 0) + $amount;

		$this->put($key, $value);

		return $value;
	}

	/**
	 * Flush the session data and regenerate the ID.
	 */
	public function invalidate(): bool
	{
		$this->flush();

		return $this->migrate(true);
	}

	/**
	 * Determine if the session has been started.
	 */
	public function isStarted(): bool
	{
		return $this->started;
	}

	/**
	 * Determine if this is a valid session ID.
	 */
	public function isValidId(string|null $id): bool
	{
		return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
	}

	/**
	 * Reflash a subset of the current flash data.
	 */
	public function keep(mixed $keys = null): void
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		$this->mergeNewFlashes($keys);

		$this->removeFromOldFlashData($keys);
	}

	/**
	 * Load the session data from the handler.
	 */
	protected function loadSession(): void
	{
		$this->attributes = array_replace($this->attributes, $this->readFromHandler());

		$this->marshalErrorBag();
	}

	/**
	 * Marshal the ViewErrorBag when using JSON serialization for sessions.
	 */
	protected function marshalErrorBag(): void
	{
		if ($this->serialization !== 'json' || $this->missing('errors')) {
			return;
		}

		$errorBag = new ViewErrorBag;

		foreach ($this->get('errors') as $key => $value) {
			$messageBag = new MessageBag($value['messages']);

			$errorBag->put($key, $messageBag->setFormat($value['format']));
		}

		$this->put('errors', $errorBag);
	}

	/**
	 * Merge new flash keys into the new flash array.
	 */
	protected function mergeNewFlashes(array $keys): void
	{
		$values = array_unique(array_merge($this->get('_flash.new', []), $keys));

		$this->put('_flash.new', $values);
	}

	/**
	 * Generate a new session ID for the session.
	 */
	public function migrate(bool $destroy = false): bool
	{
		if ($destroy) {
			$this->handler->destroy($this->getId());
		}

		$this->setExists(false);

		$this->setId($this->generateSessionId());

		return true;
	}

	/**
	 * Determine if the given key is missing from the session data.
	 */
	public function missing(string|array $key): bool
	{
		return ! $this->exists($key);
	}

	/**
	 * Flash a key / value pair to the session for immediate use.
	 */
	public function now(string $key, mixed $value): void
	{
		$this->put($key, $value);

		$this->push('_flash.old', $key);
	}

	/**
	 * Get a subset of the session data.
	 */
	public function only(array $keys): array
	{
		return Arr::only($this->attributes, $keys);
	}

	/**
	 * Specify that the user has confirmed their password.
	 */
	public function passwordConfirmed(): void
	{
		$this->put('auth.password_confirmed_at', time());
	}

	/**
	 * Prepare the ViewErrorBag instance for JSON serialization.
	 */
	protected function prepareErrorBagForSerialization(): void
	{
		if ($this->serialization !== 'json' || $this->missing('errors')) {
			return;
		}

		$errors = [];

		foreach ($this->attributes['errors']->getBags() as $key => $value) {
			$errors[$key] = [
				'format' => $value->getFormat(),
				'messages' => $value->getMessages(),
			];
		}

		$this->attributes['errors'] = $errors;
	}

	/**
	 * Prepare the serialized session data for storage.
	 */
	protected function prepareForStorage(string $data): string
	{
		return $data;
	}

	/**
	 * Prepare the raw string data from the session for unserialization.
	 */
	protected function prepareForUnserialize(string $data): string
	{
		return $data;
	}

	/**
	 * Get the previous URL from the session.
	 */
	public function previousUrl(): string|null
	{
		return $this->get('_previous.url');
	}

	/**
	 * Get the value of a given key and then forget it.
	 */
	public function pull(string $key, mixed $default = null): mixed
	{
		return Arr::pull($this->attributes, $key, $default);
	}

	/**
	 * Push a value onto a session array.
	 */
	public function push(string $key, mixed $value): void
	{
		$array = $this->get($key, []);

		$array[] = $value;

		$this->put($key, $array);
	}

	/**
	 * Put a key / value pair or array of key / value pairs in the session.
	 */
	public function put(string|array $key, mixed $value = null): void
	{
		if (! is_array($key)) {
			$key = [$key => $value];
		}

		foreach ($key as $arrayKey => $arrayValue) {
			Arr::set($this->attributes, $arrayKey, $arrayValue);
		}
	}

	/**
	 * Read the session data from the handler.
	 */
	protected function readFromHandler(): array
	{
		$data = $this->handler->read($this->getId());

		if ($data) {
			$data = $this->serialization === 'json'
				? json_decode($this->prepareForUnserialize($data), true)
				: @unserialize($this->prepareForUnserialize($data));

			if ($data !== false && is_array($data)) {
				return $data;
			}
		}

		return [];
	}

	/**
	 * Reflash all of the session flash data.
	 */
	public function reflash(): void
	{
		$this->mergeNewFlashes($this->get('_flash.old', []));

		$this->put('_flash.old', []);
	}

	/**
	 * Generate a new session identifier.
	 */
	public function regenerate(bool $destroy = false): bool
	{
		return tap($this->migrate($destroy), fn () => $this->regenerateToken());
	}

	/**
	 * Regenerate the CSRF token value.
	 */
	public function regenerateToken(): void
	{
		$this->put('_token', Str::random(40));
	}

	/**
	 * Get an item from the session, or store the default value.
	 */
	public function remember(string $key, Closure $callback): mixed
	{
		$value = $this->get($key);

		if (! is_null($value)) {
			return $value;
		}

		return tap($callback(), fn ($value) => $this->put($key, $value));
	}

	/**
	 * Remove an item from the session, returning its value.
	 */
	public function remove(string $key): mixed
	{
		return Arr::pull($this->attributes, $key);
	}

	/**
	 * Remove the given keys from the old flash data.
	 */
	protected function removeFromOldFlashData(array $keys): void
	{
		$this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
	}

	/**
	 * Replace the given session attributes entirely.
	 */
	public function replace(array $attributes): void
	{
		$this->put($attributes);
	}

	/**
	 * Save the session data to storage.
	 */
	public function save(): void
	{
		$this->ageFlashData();

		$this->prepareErrorBagForSerialization();

		$this->handler->write($this->getId(), $this->prepareForStorage(
			$this->serialization === 'json'
				? json_encode($this->attributes)
				: serialize($this->attributes)
		));

		$this->started = false;
	}

	/**
	 * Set the existence of the session on the handler if applicable.
	 */
	public function setExists(bool $value): void
	{
		if ($this->handler instanceof ExistenceAware) {
			$this->handler->setExists($value);
		}
	}

	/**
	 * Set the underlying session handler implementation.
	 */
	public function setHandler(SessionHandlerInterface $handler): SessionHandlerInterface
	{
		return $this->handler = $handler;
	}

	/**
	 * Set the session ID.
	 */
	public function setId(string $id): void
	{
		$this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
	}

	/**
	 * Set the name of the session.
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * Set the "previous" URL in the session.
	 */
	public function setPreviousUrl(string $url): void
	{
		$this->put('_previous.url', $url);
	}

	/**
	 * Set the request on the handler instance.
	 */
	public function setRequestOnHandler(Request $request): void
	{
		if ($this->handlerNeedsRequest()) {
			$this->handler->setRequest($request);
		}
	}

	/**
	 * Start the session, reading the data from a handler.
	 */
	public function start(): bool
	{
		$this->loadSession();

		if (! $this->has('_token')) {
			$this->regenerateToken();
		}

		return $this->started = true;
	}

	/**
	 * Get the CSRF token value.
	 */
	public function token(): string
	{
		return $this->get('_token');
	}
}
