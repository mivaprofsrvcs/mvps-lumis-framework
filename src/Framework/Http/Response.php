<?php

namespace MVPS\Lumis\Framework\Http;

use pdeans\Http\Response as BaseResponse;

class Response extends BaseResponse
{
	/**
	 * The HTTP response charset.
	 *
	 * @var string
	 */
	protected string $charset = 'UTF-8';

	/**
	 * Cleans or flushes output buffers up to target level.
	 *
	 * Resulting level can be greater than target level if a non-removable buffer has been encountered.
	 */
	public static function closeOutputBuffers(int $targetLevel, bool $flush): void
	{
		$status = ob_get_status(true);
		$level = count($status);
		$flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

		while (
			$level-- > $targetLevel &&
			($s = $status[$level]) &&
			(! isset($s['del']) ? ! isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])
		) {
			if ($flush) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}
		}
	}

	/**
	 * Get the HTTP response charset.
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}

	/**
	 * Prepares the response before it is sent to the client.
	 */
	public function prepare(): static
	{
		$response = $this;

		if ($response->hasHeader('Content-Type')) {
			$contentType = $response->getHeaderLine('Content-Type');

			if (stripos($contentType, 'text/') === 0 && stripos($contentType, 'charset') === false) {
				$response = $response->withHeader('Content-Type', $contentType . '; charset=' . $this->charset);
			}
		} else {
			$response = $response->withHeader('Content-Type', 'text/html; charset=' . $this->charset);
		}

		return $response;
	}

	/**
	 * Sends HTTP headers and content.
	 */
	public function send(): static
	{
		$this->sendHeaders();
		$this->sendContent();

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (! in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
			static::closeOutputBuffers(0, true);
			flush();
		}

		return $this;
	}

	/**
	 * Sends content for the current web response.
	 */
	public function sendContent(): static
	{
		echo (string) $this->getBody();

		return $this;
	}

	/**
	 * Sends HTTP headers for the current web response.
	 */
	public function sendHeaders(): static
	{
		foreach ($this->getHeaders() as $name => $values) {
			$replace = strcasecmp($name, 'Content-Type') === 0;

			foreach ($values as $value) {
				header($name . ': ' . $value, $replace, $this->getStatusCode());
			}
		}

		header(
			sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getReasonPhrase()),
			true,
			$this->getStatusCode()
		);

		return $this;
	}

	/**
	 * Set the HTTP response charset.
	 */
	public function setCharset(string $charset): static
	{
		$this->charset = $charset;

		return $this;
	}

	/**
	 * Returns the response as an HTTP string.
	 *
	 * The string representation of the response is the same as the one
	 * that will be sent to the client only if the prepare() method has
	 * has been called beforehand.
	 *
	 * @see prepare()
	 */
	public function __toString(): string
	{
		$eol = "\r\n";

		$output = sprintf(
			'HTTP/%s %s %s',
			$this->getProtocolVersion(),
			$this->getStatusCode(),
			$this->getReasonPhrase()
		) . $eol;

		foreach ($this->getHeaders() as $name => $value) {
			$output .= $name . ': ' . $this->getHeaderLine($name) . $eol;
		}

		$output .= $eol . (string) $this->getBody();

		return $output;
	}
}
