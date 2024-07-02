<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
	/**
	 * The HTTP headers.
	 */
	protected array $headers = [];

	/**
	 * The HTTP status code.
	 */
	protected int $statusCode = 0;

	/**
	 * Create a new HTTP exception instance.
	 */
	public function __construct(
		int $statusCode,
		string $message = '',
		Throwable|null $previous = null,
		array $headers = [],
		int $code = 0,
	) {
		parent::__construct($message, $code, $previous);

		$this->statusCode = $statusCode;
		$this->headers = $headers;
	}

	/**
	 * Get the HTTP headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Get the HTTP status code.
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Set the HTTP headers.
	 */
	public function setHeaders(array $headers): void
	{
		$this->headers = $headers;
	}
}
