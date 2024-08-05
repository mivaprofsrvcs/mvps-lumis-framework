<?php

namespace MVPS\Lumis\Framework\Routing\Exceptions;

use MVPS\Lumis\Framework\Http\Response;
use RuntimeException;
use Throwable;

class StreamedResponseException extends RuntimeException
{
	/**
	 * The actual exception thrown during the stream.
	 *
	 * @var Throwable
	 */
	public Throwable $originalException;

	/**
	 * Create a new streamed response exception instance.
	 */
	public function __construct(Throwable $originalException)
	{
		$this->originalException = $originalException;

		parent::__construct($originalException->getMessage());
	}

	/**
	 * Get the actual exception thrown during the stream.
	 */
	public function getInnerException(): Throwable
	{
		return $this->originalException;
	}

	/**
	 * Render the exception.
	 */
	public function render(): Response
	{
		return new Response('');
	}
}
