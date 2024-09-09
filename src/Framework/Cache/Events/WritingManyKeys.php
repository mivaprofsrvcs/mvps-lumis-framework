<?php

namespace MVPS\Lumis\Framework\Cache\Events;

class WritingManyKeys extends CacheEvent
{
	/**
	 * The keys that are being written.
	 *
	 * @var mixed
	 */
	public mixed $keys;

	/**
	 * The number of seconds the keys should be valid.
	 *
	 * @var int|null
	 */
	public int|null $seconds;

	/**
	 * The values that will be written.
	 *
	 * @var mixed
	 */
	public mixed $values;

	/**
	 * Create a new writing many keys event instance.
	 */
	public function __construct(
		string|null $storeName,
		array $keys,
		array $values,
		int|null $seconds = null,
		array $tags = []
	) {
		parent::__construct($storeName, $keys[0], $tags);

		$this->keys = $keys;
		$this->values = $values;
		$this->seconds = $seconds;
	}
}
