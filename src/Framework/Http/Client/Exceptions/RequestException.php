<?php

namespace MVPS\Lumis\Framework\Http\Client\Exceptions;

use MVPS\Lumis\Framework\Http\Client\Response;
use Psr\Http\Message\MessageInterface;

class RequestException extends HttpClientException
{
	/**
	 * The response instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Client\Response\Response
	 */
	public Response $response;

	/**
	 * Create a new request exception exception instance.
	 */
	public function __construct(Response $response)
	{
		parent::__construct($this->prepareMessage($response), $response->status());

		$this->response = $response;
	}

	/**
	 * Get a short summary of the message body.
	 *
	 * Will return null if the response is not printable.
	 */
	public function bodySummary(MessageInterface $message, int $truncateAt = 120): string|null
	{
		$body = $message->getBody();

		if (! $body->isSeekable() || ! $body->isReadable()) {
			return null;
		}

		$size = $body->getSize();

		if ($size === 0) {
			return null;
		}

		$body->rewind();

		$summary = $body->read($truncateAt);

		$body->rewind();

		if ($size > $truncateAt) {
			$summary .= ' (truncated...)';
		}

		// Matches any printable Unicode character.
		if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/u', $summary) !== 0) {
			return null;
		}

		return $summary;
	}

	/**
	 * Prepare the exception message.
	 */
	protected function prepareMessage(Response $response): string
	{
		$message = 'HTTP request returned status code ' . $response->status();

		$summary = $this->bodySummary($response->toPsrResponse());

		return is_null($summary) ? $message : $message .= ":\n{$summary}\n";
	}
}
