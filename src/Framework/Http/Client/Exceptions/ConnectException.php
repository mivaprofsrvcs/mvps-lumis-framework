<?php

namespace MVPS\Lumis\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Throwable;

class ConnectException extends TransferException
{
	/**
	 * Additional context provided by the handler.
	 *
	 * @var array
	 */
	protected array $handlerContext;

	/**
	 * The request that caused the exception.
	 *
	 * @var RequestInterface
	 */
	protected RequestInterface $request;

	/**
	 * Create a new connect exception instance.
	 */
	public function __construct(
		string $message,
		RequestInterface $request,
		Throwable|null $previous = null,
		array $handlerContext = []
	) {
		parent::__construct($message, 0, $previous);

		$this->request = $request;
		$this->handlerContext = $handlerContext;
	}

	/**
	 * Get the request that caused the exception.
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}

	/**
	 * Gets the additional context provided by the handler.
	 */
	public function getHandlerContext(): array
	{
		return $this->handlerContext;
	}
}
