<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Http\Exceptions;

use MVPS\Lumis\Framework\Contracts\Http\HttpException as HttpExceptionContract;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionContract
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
	 * Creates an appropriate HTTP exception based on the given status code.
	 *
	 * Maps the provided status code to a specific HTTP exception subclass.
	 * If no matching subclass is found, a generic HTTP exception is created.
	 */
	public static function fromStatusCode(
		int $statusCode,
		string $message = '',
		Throwable|null $previous = null,
		array $headers = [],
		int $code = 0
	): static {
		return match ($statusCode) {
			400 => new BadRequestException($message, $previous, $code, $headers),
			403 => new AccessDeniedException($message, $previous, $code, $headers),
			404 => new NotFoundException($message, $previous, $code, $headers),
			406 => new NotAcceptableException($message, $previous, $code, $headers),
			409 => new ConflictException($message, $previous, $code, $headers),
			410 => new GoneException($message, $previous, $code, $headers),
			411 => new LengthRequiredException($message, $previous, $code, $headers),
			412 => new PreconditionFailedException($message, $previous, $code, $headers),
			413 => new ContentTooLargeException($message, $previous, $code, $headers),
			415 => new UnsupportedMediaTypeException($message, $previous, $code, $headers),
			422 => new UnprocessableEntityException($message, $previous, $code, $headers),
			423 => new LockedException($message, $previous, $code, $headers),
			428 => new PreconditionRequiredException($message, $previous, $code, $headers),
			429 => new TooManyRequestsException(null, $message, $previous, $code, $headers),
			503 => new ServiceUnavailableException(null, $message, $previous, $code, $headers),
			default => new static($statusCode, $message, $previous, $headers, $code),
		};
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
