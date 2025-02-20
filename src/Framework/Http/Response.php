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

namespace MVPS\Lumis\Framework\Http;

use ArrayObject;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use MVPS\Lumis\Framework\Contracts\Support\Renderable;
use MVPS\Lumis\Framework\Http\Traits\ResponseTrait;
use MVPS\Lumis\Framework\Http\Traits\StatusCodes;
use pdeans\Http\Factories\StreamFactory;
use pdeans\Http\Response as BaseResponse;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Response extends BaseResponse
{
	use StatusCodes;
	use ResponseTrait;
	use Macroable {
		Macroable::__call as macroCall;
	}

	/**
	 * Mapping of cache control directives to whether they accept a value.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
	 *
	 * @var array<string, bool>
	 */
	private const HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES = [
		'etag' => true,
		'immutable' => false,
		'last_modified' => true,
		'max_age' => true,
		'must_revalidate' => false,
		'no_cache' => false,
		'no_store' => false,
		'no_transform' => false,
		'private' => false,
		'proxy_revalidate' => false,
		'public' => false,
		's_maxage' => true,
		'stale_if_error' => true, // RFC5861
		'stale_while_revalidate' => true, // RFC5861
	];

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
	protected string $content = '';

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
	public function getContent(): string|false
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
	 * Retrieves the value of the ETag header.
	 */
	public function getEtag(): string|null
	{
		return $this->headerBag->get('ETag');
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
	 * Retrieves the Last-Modified header as a DateTimeImmutable instance.
	 *
	 * @throws \RuntimeException
	 */
	public function getLastModified(): DateTimeImmutable|null
	{
		return $this->headerBag->getDate('Last-Modified');
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
	 * Determines if the response status code indicates a client error.
	 */
	public function isClientError(): bool
	{
		return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
	}

	/**
	 * Determines if the response status code indicates an empty response.
	 */
	public function isEmpty(): bool
	{
		return in_array($this->getStatusCode(), [204, 304]);
	}

	/**
	 * Determines if the response status code is specifically 403 (Forbidden).
	 */
	public function isForbidden(): bool
	{
		return $this->getStatusCode() === 403;
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
	 * Determines if the response status code is informational.
	 */
	public function isInformational(): bool
	{
		return $this->getStatusCode() >= static::MIN_STATUS_CODE_VALUE &&
			$this->getStatusCode() < 200;
	}

	/**
	 * Determines if the response status code is invalid.
	 *
	 * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	 */
	public function isInvalid(): bool
	{
		return $this->getStatusCode() < static::MIN_STATUS_CODE_VALUE ||
			$this->getStatusCode() > static::MAX_STATUS_CODE_VALUE;
	}

	/**
	 * Determines if the response status code is specifically 404 (Not Found).
	 */
	public function isNotFound(): bool
	{
		return $this->getStatusCode() === 404;
	}

	/**
	 * Determines if the response has been modified since the
	 * client's last request.
	 */
	public function isNotModified(Request $request): bool
	{
		if (! $request->isMethodCacheable()) {
			return false;
		}

		$notModified = false;
		$lastModified = $this->headerBag->get('Last-Modified');
		$modifiedSince = $request->headerBag->get('If-Modified-Since');

		$ifNoneMatchEtags = $request->getETags();
		$etag = $this->getEtag();

		if ($ifNoneMatchEtags && ! is_null($etag)) {
			if (strncmp($etag, 'W/', 2) === 0) {
				$etag = substr($etag, 2);
			}

			// Use weak comparison for entity tag matching as recommended by
			// RFC 7232 section 3.2.
			foreach ($ifNoneMatchEtags as $ifNoneMatchEtag) {
				if (strncmp($ifNoneMatchEtag, 'W/', 2) === 0) {
					$ifNoneMatchEtag = substr($ifNoneMatchEtag, 2);
				}

				if ($ifNoneMatchEtag === $etag || $ifNoneMatchEtag === '*') {
					$notModified = true;

					break;
				}
			}
		} elseif ($modifiedSince && $lastModified) {
			// Only consider If-Modified-Since if If-None-Match is not present.
			// As per RFC 7232 section 3.3, If-None-Match is a stronger validator
			// and should be used over If-Modified-Since for cache validation.
			$notModified = strtotime($modifiedSince) >= strtotime($lastModified);
		}

		if ($notModified) {
			$this->setNotModified();
		}

		return $notModified;
	}

	/**
	 * Determines if the response status code is specifically 200 (OK).
	 */
	public function isOk(): bool
	{
		return $this->getStatusCode() === 200;
	}

	/**
	 * Determines if the response is a redirect and optionally validates
	 * the location header.
	 */
	public function isRedirect(string|null $location = null): bool
	{
		return in_array($this->getStatusCode(), [201, 301, 302, 303, 307, 308]) &&
			(is_null($location) ?: $location === $this->headerBag->get('Location'));
	}

	/**
	 * Determines if the response status code indicates a redirection.
	 */
	public function isRedirection(): bool
	{
		return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
	}

	/**
	 * Determines if the response status code indicates a server error.
	 */
	public function isServerError(): bool
	{
		return $this->getStatusCode() >= 500 &&
			$this->getStatusCode() <= static::MAX_STATUS_CODE_VALUE;
	}

	/**
	 * Determines if the response status code indicates success.
	 */
	public function isSuccessful(): bool
	{
		return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
	}

	/**
	 * Determines if the response is validatable.
	 */
	public function isValidateable(): bool
	{
		return $this->headerBag->has('Last-Modified') || $this->headerBag->has('ETag');
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
	public function prepare(Request $request): static
	{
		$headers = $this->headerBag;

		if ($this->isInformational() || $this->isEmpty()) {
			$this->setContent(null);

			$headers->remove('Content-Type');
			$headers->remove('Content-Length');

			// Prevent PHP from automatically setting the Content-Type header
			// based on the default MIME type.
			ini_set('default_mimetype', '');
		} else {
			if (! $headers->has('Content-Type')) {
				$format = $request->getRequestFormat(null);

				if (! is_null($format) && $mimeType = $request->getMimeType($format)) {
					$headers->set('Content-Type', $mimeType);
				}
			}

			// Ensure correct Content-Type header is set based on response content.
			$charset = $this->charset ?: 'UTF-8';

			if (! $headers->has('Content-Type')) {
				$headers->set('Content-Type', 'text/html; charset=' . $charset);
			} elseif (
				stripos($headers->get('Content-Type') ?? '', 'text/') === 0 &&
				stripos($headers->get('Content-Type') ?? '', 'charset') === false
			) {
				$headers->set('Content-Type', $headers->get('Content-Type') . '; charset=' . $charset);
			}

			// Remove Content-Length header if Transfer-Encoding is present.
			// Transfer-Encoding takes precedence over Content-Length.
			if ($headers->has('Transfer-Encoding')) {
				$headers->remove('Content-Length');
			}

			// As per RFC 2616 section 14.13, remove the content body for HEAD
			// requests while preserving the Content-Length header if present.
			if ($request->isMethod('HEAD')) {
				$length = $headers->get('Content-Length');

				$this->setContent(null);

				if ($length) {
					$headers->set('Content-Length', $length);
				}
			}
		}

		// For HTTP/1.0, add 'Pragma: no-cache' and 'Expires: -1' headers if
		// 'Cache-Control: no-cache' is present. This ensures compatibility
		// with older clients
		if ($this->getProtocolVersion() === '1.0' && str_contains($headers->get('Cache-Control', ''), 'no-cache')) {
			$headers->set('pragma', 'no-cache');
			$headers->set('expires', -1);
		}

		if ($request->isSecure()) {
			foreach ($headers->getCookies() as $cookie) {
				$cookie->setSecureDefault(true);
			}
		}

		return $this;
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
	 * Sets cache-related headers for the response.
	 *
	 * Available options are:
	 *   - etag
	 *   - immutable
	 *   - last_modified
	 *   - max_age
	 *   - must_revalidate
	 *   - no_cache
	 *   - no_store
	 *   - no_transform,
	 *   - private
	 *   - proxy_revalidate
	 *   - public
	 *   - s_maxage
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setCache(array $options): static
	{
		$diff = array_diff(array_keys($options), array_keys(static::HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES));

		if ($diff) {
			throw new InvalidArgumentException(
				sprintf('Response does not support the following options: "%s".', implode('", "', $diff))
			);
		}

		if (isset($options['etag'])) {
			$this->setEtag($options['etag']);
		}

		if (isset($options['last_modified'])) {
			$this->setLastModified($options['last_modified']);
		}

		if (isset($options['max_age'])) {
			$this->setMaxAge($options['max_age']);
		}

		if (isset($options['s_maxage'])) {
			$this->setSharedMaxAge($options['s_maxage']);
		}

		if (isset($options['stale_while_revalidate'])) {
			$this->setStaleWhileRevalidate($options['stale_while_revalidate']);
		}

		if (isset($options['stale_if_error'])) {
			$this->setStaleIfError($options['stale_if_error']);
		}

		foreach (static::HTTP_RESPONSE_CACHE_CONTROL_DIRECTIVES as $directive => $hasValue) {
			if (! $hasValue && isset($options[$directive])) {
				if ($options[$directive]) {
					$this->headerBag->addCacheControlDirective(str_replace('_', '-', $directive));
				} else {
					$this->headerBag->removeCacheControlDirective(str_replace('_', '-', $directive));
				}
			}
		}

		if (isset($options['public'])) {
			if ($options['public']) {
				$this->setPublic();
			} else {
				$this->setPrivate();
			}
		}

		if (isset($options['private'])) {
			if ($options['private']) {
				$this->setPrivate();
			} else {
				$this->setPublic();
			}
		}

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
		}

		$this->content = $content ?? '';

		return $this;
	}

	/**
	 * Sets the ETag header value.
	 */
	public function setEtag(string|null $etag, bool $weak = false): static
	{
		if (is_null($etag)) {
			$this->headerBag->remove('Etag');
		} else {
			if (! str_starts_with($etag, '"')) {
				$etag = '"' . $etag . '"';
			}

			$this->headerBag->set('ETag', ($weak ? 'W/' : '') . $etag);
		}

		return $this;
	}

	/**
	 * Sets the Last-Modified HTTP header.
	 */
	public function setLastModified(DateTimeInterface|null $date): static
	{
		if (is_null($date)) {
			$this->headerBag->remove('Last-Modified');

			return $this;
		}

		$date = (DateTimeImmutable::createFromInterface($date))->setTimezone(new DateTimeZone('UTC'));

		$this->headerBag->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');

		return $this;
	}

	/**
	 * Sets the maximum age of the response in seconds.
	 *
	 * Controls the freshness lifetime of the response.
	 */
	public function setMaxAge(int $value): static
	{
		$this->headerBag->addCacheControlDirective('max-age', $value);

		return $this;
	}

	/**
	 * Prepares the response for a 304 Not Modified status code.
	 *
	 * @see https://tools.ietf.org/html/rfc2616#section-10.3.5
	 */
	public function setNotModified(): static
	{
		$this->setContent(null);

		// Remove headers prohibited by RFC 2616 for 304 Not Modified responses.
		foreach (
			[
				'Allow',
				'Content-Encoding',
				'Content-Language',
				'Content-Length',
				'Content-MD5',
				'Content-Type',
				'Last-Modified'
			] as $header
		) {
			$this->headerBag->remove($header);
		}

		return $this;
	}

	/**
	 * Sets the "private" cache control directive.
	 *
	 * Prevents the response from being cached by intermediate proxies.
	 */
	public function setPrivate(): static
	{
		$this->headerBag->removeCacheControlDirective('public');

		$this->headerBag->addCacheControlDirective('private');

		return $this;
	}

	/**
	 * Sets the "public" cache control directive.
	 *
	 * Indicates that the response is cacheable by any client.
	 */
	public function setPublic(): static
	{
		$this->headerBag->addCacheControlDirective('public');

		$this->headerBag->removeCacheControlDirective('private');

		return $this;
	}

	/**
	 * Sets the shared maximum age for the response.
	 *
	 * Configures the Cache-Control "s-maxage" directive for shared caches.
	 */
	public function setSharedMaxAge(int $value): static
	{
		$this->setPublic();

		$this->headerBag->addCacheControlDirective('s-maxage', $value);

		return $this;
	}

	/**
	 * Sets the `stale-if-error` cache control directive.
	 *
	 * Specifies the maximum age of a stale response that can be used
	 * in case of backend errors.
	 */
	public function setStaleIfError(int $value): static
	{
		$this->headerBag->addCacheControlDirective('stale-if-error', $value);

		return $this;
	}

	/**
	 * Sets the stale-while-revalidate cache control directive
	 *
	 * Specifies the maximum age for which a stale response can be used
	 * before a revalidation is required.
	 */
	public function setStaleWhileRevalidate(int $value): static
	{
		$this->headerBag->addCacheControlDirective('stale-while-revalidate', $value);

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
	 * Creates a 304 Not Modified response.
	 *
	 * This response indicates that the requested resource has not been
	 * modified since the version specified in the request's
	 * If-Modified-Since header (if present). Clients with a cached copy can
	 * continue using it instead of fetching a new version from the server.
	 *
	 * Follows guidelines from RFC 7232 section 3.3 for handling
	 * 304 Not Modified responses.
	 *
	 * @see https://tools.ietf.org/html/rfc7232#section.3.3
	 */
	public function withNotModified(): Response
	{
		$this->setNotModified();

		return new static($this->content, 304, $this->headerBag->all());
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function withStatus(int $code, string $reasonPhrase = ''): static
	{
		return parent::withStatus($code, $reasonPhrase);
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
