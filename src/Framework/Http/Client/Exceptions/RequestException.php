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
