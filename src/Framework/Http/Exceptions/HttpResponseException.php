<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use MVPS\Lumis\Framework\Http\Response;
use RuntimeException;

class HttpResponseException extends RuntimeException
{
	/**
	 * The underlying response instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Response
	 */
	protected Response $response;

	/**
	 * Create a new HTTP response exception instance.
	 */
	public function __construct(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * Get the underlying response instance.
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}
}
