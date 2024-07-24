<?php

namespace MVPS\Lumis\Framework\Log\Events;

class MessageLogged
{
	/**
	 * The log context.
	 *
	 * @var array
	 */
	public array $context;

	/**
	 * The log "level".
	 *
	 * @var string
	 */
	public string $level;

	/**
	 * The log message.
	 *
	 * @var string
	 */
	public string $message;

	/**
	 * Create a new messaged logged event instance.
	 */
	public function __construct(string $level, string $message, array $context = [])
	{
		$this->level = $level;
		$this->message = $message;
		$this->context = $context;
	}
}
