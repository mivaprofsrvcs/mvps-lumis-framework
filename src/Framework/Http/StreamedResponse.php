<?php

namespace MVPS\Lumis\Framework\Http;

use Closure;
use LogicException;

class StreamedResponse extends Response
{
	/**
	 * The callback function to be executed for each chunk of data.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $callback = null;

	/**
	 * Tracks whether the response headers have been sent.
	 *
	 * @var bool
	 */
	private bool $headersSent = false;

	/**
	 * Indicates whether the response has been streamed.
	 *
	 * @var bool
	 */
	protected bool $streamed = false;

	/**
	 * Create a new streamed HTTP response instance.
	 */
	public function __construct(callable|null $callback = null, int $status = 200, array $headers = [])
	{
		parent::__construct(null, $status, $headers);

		if (! is_null($callback)) {
			$this->setCallback($callback);
		}
	}

	/**
	 * Retrieves the callback function for the response.
	 */
	public function getCallback(): Closure|null
	{
		if (! isset($this->callback)) {
			return null;
		}

		return ($this->callback)(...);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getContent(): string|false
	{
		return false;
	}

	/**
	 * Sends the response content.
	 *
	 * Executes the callback function to stream the content,
	 * ensuring it's sent only once.
	 */
	public function sendContent(): static
	{
		if ($this->streamed) {
			return $this;
		}

		$this->streamed = true;

		if (! isset($this->callback)) {
			throw new LogicException('The response callback must be set.');
		}

		($this->callback)();

		return $this;
	}

	/**
	 * Sends HTTP headers if not already sent.
	 */
	public function sendHeaders(int|null $statusCode = null): static
	{
		if ($this->headersSent) {
			return $this;
		}

		if ($statusCode < static::MIN_STATUS_CODE_VALUE || $statusCode >= 200) {
			$this->headersSent = true;
		}

		return parent::sendHeaders($statusCode);
	}

	/**
	 * Sets the callback function for streaming the response content.
	 */
	public function setCallback(callable $callback): static
	{
		$this->callback = $callback(...);

		return $this;
	}

	/**
	 * Prevents setting content on a streamed response.
	 *
	 * @throws \LogicException
	 */
	public function setContent(mixed $content): static
	{
		if (! is_null($content)) {
			throw new LogicException(
				'Setting content on a StreamedResponse is not supported.' .
				' Use the callback property to provide content.'
			);
		}

		$this->streamed = true;

		return $this;
	}
}
