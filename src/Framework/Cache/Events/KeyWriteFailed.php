<?php

namespace MVPS\Lumis\Framework\Cache\Events;

class KeyWriteFailed extends CacheEvent
{
	/**
	 * The number of seconds the key should be valid.
	 *
	 * @var int|null
	 */
	public int|null $seconds;

	/**
	 * The value that will be written.
	 *
	 * @var mixed
	 */
	public mixed $value;

	/**
	 * Create a new key write failed event instance.
	 */
	public function __construct(
		string|null $storeName,
		string $key,
		mixed $value,
		int|null $seconds = null,
		array $tags = []
	) {
		parent::__construct($storeName, $key, $tags);

		$this->value = $value;
		$this->seconds = $seconds;
	}
}
