<?php

namespace MVPS\Lumis\Framework\Http;

use ArrayObject;
use DateTimeImmutable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use MVPS\Lumis\Framework\Contracts\Support\Renderable;
use MVPS\Lumis\Framework\Http\Traits\ResponseTrait;
use pdeans\Http\Factories\StreamFactory;
use pdeans\Http\Response as BaseResponse;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Response extends BaseResponse
{
	use ResponseTrait;
	use Macroable {
		Macroable::__call as macroCall;
	}

	/**
	 * The HTTP response charset.
	 *
	 * @var string
	 */
	protected string $charset = 'UTF-8';

	/**
	 * The HTTP response content.
	 *
	 * @var string
	 */
	protected string $content;

	/**
	 * The header bag instance for the headers.
	 *
	 * @var \Symfony\Component\HttpFoundation\ResponseHeaderBag
	 */
	public ResponseHeaderBag $headerBag;

	/**
	 * Stores a list of headers already sent in informational responses.
	 *
	 * @var array
	 */
	private array $sentHeaders = [];

	/**
	 * Create a new HTTP response instance.
	 */
	public function __construct(mixed $content = '', $status = 200, array $headers = [])
	{
		$this->headerBag = new ResponseHeaderBag($headers);

		$this->setContent($content);

		parent::__construct(
			body: (new StreamFactory)->createStream($this->content),
			status: $status,
			headers: $this->headerBag->all()
		);
	}

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
	 * Determines if the response is cacheable by a shared (surrogate) cache.
	 *
	 * This method follows the guidelines outlined in RFC 7231 and RFC 7234 for
	 * cacheability.
	 *
	 * A response is considered cacheable if it meets the following criteria:
	 *
	 * 1. Status code: The response status code is one of the commonly cacheable
	 *    codes (e.g., 200, 301, 404).
	 * 2. Cache-Control directives: The response does not have `no-store`
	 *    directive or a private cache directive.
	 * 3. Freshness or validation information: The response includes either a
	 *    freshness lifetime (Expires, max-age) or a cache validator
	 *    (Last-Modified, ETag).
	 *
	 * Note that RFC 7231 allows for more permissive implementations, but this
	 * method prioritizes strict adherence to the standard.
	 */
	public function isCacheable(): bool
	{
		if (! in_array($this->getStatusCode(), [200, 203, 300, 301, 302, 404, 410])) {
			return false;
		}

		if (
			$this->headerBag->hasCacheControlDirective('no-store') ||
			$this->headerBag->getCacheControlDirective('private')
		) {
			return false;
		}

		return $this->isValidateable() || $this->isFresh();
	}

	/**
	 * Determines if the response is considered fresh.
	 *
	 * A fresh response can be served from cache without contacting the origin
	 * server. This is based on the presence of Cache-Control/max-age or
	 * Expires headers and their calculated age.
	 */
	public function isFresh(): bool
	{
		return $this->getTtl() > 0;
	}

	/**
	 * Determines if the response is validatable.
	 */
	public function isValidateable(): bool
	{
		return $this->headerBag->has('Last-Modified') || $this->headerBag->has('ETag');
	}

	/**
	 * Calculates the age of the response in seconds.
	 */
	public function getAge(): int
	{
		$age = $this->headerBag->get('Age');

		if (! is_null($age)) {
			return (int) $age;
		}

		return max(time() - (int) $this->getDate()->format('U'), 0);
	}

	/**
	 * Get the HTTP response charset.
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}

	/**
	 * Gets the current response content.
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * Retrieves the Date header as a DateTimeImmutable instance.
	 *
	 * @throws \RuntimeException
	 */
	public function getDate(): DateTimeImmutable|null
	{
		return $this->headerBag->getDate('Date');
	}

	/**
	 * Retrieves the Expires header as a DateTimeImmutable instance.
	 */
	public function getExpires(): DateTimeImmutable|null
	{
		try {
			return $this->headerBag->getDate('Expires');
		} catch (RuntimeException) {
			// Invalid date formats (e.g., "0", "-1") must be treated as past
			// dates according to RFC 2616.
			return DateTimeImmutable::createFromFormat('U', time() - 172800);
		}
	}

	/**
	 * Calculates the maximum age of the response in seconds.
	 */
	public function getMaxAge(): int|null
	{
		if ($this->headerBag->hasCacheControlDirective('s-maxage')) {
			return (int) $this->headerBag->getCacheControlDirective('s-maxage');
		}

		if ($this->headerBag->hasCacheControlDirective('max-age')) {
			return (int) $this->headerBag->getCacheControlDirective('max-age');
		}

		$expires = $this->getExpires();

		if (! is_null($expires)) {
			$maxAge = (int) $expires->format('U') - (int) $this->getDate()->format('U');

			return max($maxAge, 0);
		}

		return null;
	}

	/**
	 * Calculates the time-to-live (TTL) for the response in seconds.
	 *
	 * Determines the remaining validity time based on the `max-age` directive
	 * and response age. Returns null if no freshness information is available.
	 * A TTL of 0 indicates that the response may require revalidation before
	 * serving from cache.
	 */
	public function getTtl(): int|null
	{
		$maxAge = $this->getMaxAge();

		return null !== $maxAge ? max($maxAge - $this->getAge(), 0) : null;
	}

	/**
	 * Transform the given content to JSON.
	 */
	protected function morphToJson(mixed $content): string|bool
	{
		if ($content instanceof Jsonable) {
			return $content->toJson();
		} elseif ($content instanceof Arrayable) {
			return json_encode($content->toArray());
		}

		return json_encode($content);
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
	 *
	 * Optional parameter to determine if the output buffers should be flushed.
	 */
	public function send(bool $flush = true): static
	{
		$this->sendHeaders();
		$this->sendContent();

		if (! $flush) {
			return $this;
		}

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (! in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
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
		echo $this->content;

		return $this;
	}

	/**
	 * Sends HTTP headers to the client.
	 *
	 * Optional status code to override the default.
	 */
	public function sendHeaders(int|null $statusCode = null): static
	{
		// Headers already sent, no need to send again.
		if (headers_sent()) {
			return $this;
		}

		$informationalResponse = $statusCode >= static::MIN_STATUS_CODE_VALUE && $statusCode < 200;

		// Skip informational responses if not supported by the SAPI.
		if ($informationalResponse && ! function_exists('headers_send')) {
			return $this;
		}

		foreach ($this->headerBag->allPreserveCaseWithoutCookies() as $name => $values) {
			// PHP automatically copies headers from previous 103 responses
			// (RFC 8297), so we need to handle potential header changes.
			$previousValues = $this->sentHeaders[$name] ?? null;

			// Header already sent in a previous 103 response, will be copied
			// automatically by PHP.
			if ($previousValues === $values) {
				continue;
			}

			$replace = strcasecmp($name, 'Content-Type') === 0;

			if (! is_null($previousValues) && array_diff($previousValues, $values)) {
				header_remove($name);

				$previousValues = null;
			}

			$newValues = is_null($previousValues) ? $values : array_diff($values, $previousValues);

			foreach ($newValues as $value) {
				header($name . ': ' . $value, $replace, $this->getStatusCode());
			}

			if ($informationalResponse) {
				$this->sentHeaders[$name] = $values;
			}
		}

		// Handle cookie headers
		foreach ($this->headerBag->getCookies() as $cookie) {
			header('Set-Cookie: ' . $cookie, false, $this->getStatusCode());
		}

		if ($informationalResponse) {
			headers_send($statusCode);

			return $this;
		}

		$statusCode ??= $this->getStatusCode();

		header(
			sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $statusCode, $this->getReasonPhrase()),
			true,
			$statusCode
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
	 * Set the content on the response.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setContent(mixed $content): static
	{
		$this->original = $content;

		if ($this->shouldBeJson($content)) {
			$this->header('Content-Type', 'application/json');

			$content = $this->morphToJson($content);

			if ($content === false) {
				throw new InvalidArgumentException(json_last_error_msg());
			}
		} elseif ($content instanceof Renderable) {
			$content = $content->render();
		} else {
			$content = (string) $content;
		}

		$this->content = $content;

		return $this;
	}

	/**
	 * Determine if the given content should be transformed into JSON.
	 */
	public function shouldBeJson(mixed $content): bool
	{
		return $content instanceof Arrayable ||
			$content instanceof Jsonable ||
			$content instanceof ArrayObject ||
			$content instanceof JsonSerializable ||
			$content instanceof stdClass ||
			is_array($content);
	}

	/**
	 * Returns the response as a string representation.
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

		$output .= $this->headerBag . $eol;

		$output .= $this->getContent();

		return $output;
	}
}
