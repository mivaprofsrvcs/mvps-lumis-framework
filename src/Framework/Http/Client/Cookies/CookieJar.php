<?php

namespace MVPS\Lumis\Framework\Http\Client\Cookies;

use ArrayIterator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CookieJar
{
	/**
	 * Array of cookies managed by the jar.
	 *
	 * @var array
	 */
	private array $cookies = [];

	/**
	 * Indicates whether to enforce strict cookie validation.
	 *
	 * If true, invalid cookies will throw exceptions.
	 *
	 * @var bool
	 */
	private bool $strictMode;

	/**
	 * Create a new cookie jar instance.
	 *
	 * Initializes the cookie jar with optional strict mode and initial cookies.
	 */
	public function __construct(bool $strictMode = false, array $cookies = [])
	{
		$this->strictMode = $strictMode;

		foreach ($cookies as $cookie) {
			if (! $cookie instanceof SetCookie) {
				$cookie = new SetCookie($cookie);
			}
			$this->setCookie($cookie);
		}
	}

	/**
	 * Clears cookies from the jar based on given criteria.
	 */
	public function clear(string|null $domain = null, string|null $path = null, string|null $name = null): void
	{
		if (! $domain) {
			$this->cookies = [];

			return;
		} elseif (! $path) {
			$this->cookies = array_filter(
				$this->cookies,
				fn (SetCookie $cookie) => ! $cookie->matchesDomain($domain)
			);
		} elseif (!$name) {
			$this->cookies = array_filter(
				$this->cookies,
				fn (SetCookie $cookie) => ! $cookie->matchesPath($path) && $cookie->matchesDomain($domain)
			);
		} else {
			$this->cookies = array_filter(
				$this->cookies,
				fn (SetCookie $cookie) => $cookie->getName() !== $name &&
					$cookie->matchesPath($path) &&
					$cookie->matchesDomain($domain)
			);
		}
	}

	/**
	 * Removes session cookies from the jar.
	 */
	public function clearSessionCookies(): void
	{
		$this->cookies = array_filter(
			$this->cookies,
			fn (SetCookie $cookie) => ! $cookie->getDiscard() && $cookie->getExpires()
		);
	}

	/**
	 * Returns the number of cookies in the jar.
	 */
	public function count(): int
	{
		return count($this->cookies);
	}

	/**
	 * Extracts cookies from the response headers and stores them in the jar.
	 */
	public function extractCookies(RequestInterface $request, ResponseInterface $response): void
	{
		$cookieHeader = $response->getHeader('Set-Cookie');

		if (! $cookieHeader) {
			return;
		}

		foreach ($cookieHeader as $cookie) {
			$setCookie = SetCookie::fromString($cookie);

			if (! $setCookie->getDomain()) {
				$setCookie->setDomain($request->getUri()->getHost());
			}

			if (strpos($setCookie->getPath(), '/') !== 0) {
				$setCookie->setPath($this->getCookiePathFromRequest($request));
			}

			if (! $setCookie->matchesDomain($request->getUri()->getHost())) {
				continue;
			}

			$this->setCookie($setCookie);
		}
	}

	/**
	 * Create a new cookie jar from an associative array and domain.
	 */
	public static function fromArray(array $cookies, string $domain): static
	{
		$cookieJar = new static();

		foreach ($cookies as $name => $value) {
			$cookieJar->setCookie(new SetCookie([
				'Domain' => $domain,
				'Name' => $name,
				'Value' => $value,
				'Discard' => true,
			]));
		}

		return $cookieJar;
	}

	/**
	 * Finds and returns the cookie based on the name.
	 */
	public function getCookieByName(string $name): SetCookie|null
	{
		foreach ($this->cookies as $cookie) {
			if (! is_null($cookie->getName()) && strcasecmp($cookie->getName(), $name) === 0) {
				return $cookie;
			}
		}

		return null;
	}

	/**
	 * Computes cookie path following RFC 6265 section 5.1.4
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc6265#section-5.1.4
	 */
	private function getCookiePathFromRequest(RequestInterface $request): string
	{
		$uriPath = $request->getUri()->getPath();

		if ($uriPath === '' || $uriPath === '/' || strpos($uriPath, '/') !== 0) {
			return '/';
		}

		$lastSlashPos = strrpos($uriPath, '/');

		if ($lastSlashPos === 0 || $lastSlashPos === false) {
			return '/';
		}

		return substr($uriPath, 0, $lastSlashPos);
	}

	/**
	 * Returns an iterator for iterating over the cookies in the jar.
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator(array_values($this->cookies));
	}

	/**
	 * If a cookie already exists and the server asks to set it again with a
	 * null value, the cookie must be deleted.
	 */
	private function removeCookieIfEmpty(SetCookie $cookie): void
	{
		$cookieValue = $cookie->getValue();

		if (is_null($cookieValue) || $cookieValue === '') {
			$this->clear(
				$cookie->getDomain(),
				$cookie->getPath(),
				$cookie->getName()
			);
		}
	}

	/**
	 * Sets a cookie in the cookie jar.
	 *
	 * Validates the cookie and checks for existing cookies with the same name,
	 * path, and domain. If a matching cookie exists, it is replaced if the new
	 * cookie has a later expiration time or is not discardable. Otherwise, the
	 * existing cookie is removed.
	 *
	 * @throws \RuntimeException
	 */
	public function setCookie(SetCookie $cookie): bool
	{
		$name = $cookie->getName();

		if (! $name && $name !== '0') {
			return false;
		}

		$result = $cookie->validate();

		if ($result !== true) {
			if ($this->strictMode) {
				throw new RuntimeException('Invalid cookie: ' . $result);
			}

			$this->removeCookieIfEmpty($cookie);

			return false;
		}

		foreach ($this->cookies as $i => $c) {
			if (
				$c->getPath() != $cookie->getPath() ||
				$c->getDomain() != $cookie->getDomain() ||
				$c->getName() != $cookie->getName()
			) {
				continue;
			}

			if (! $cookie->getDiscard() && $c->getDiscard()) {
				unset($this->cookies[$i]);

				continue;
			}

			if ($cookie->getExpires() > $c->getExpires()) {
				unset($this->cookies[$i]);

				continue;
			}

			if ($cookie->getValue() !== $c->getValue()) {
				unset($this->cookies[$i]);

				continue;
			}

			return false;
		}

		$this->cookies[] = $cookie;

		return true;
	}

	/**
	 * Evaluate if this cookie should be persisted to storage that survives
	 * between requests.
	 */
	public static function shouldPersist(SetCookie $cookie, bool $allowSessionCookies = false): bool
	{
		if ($cookie->getExpires() || $allowSessionCookies) {
			if (! $cookie->getDiscard()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds cookie headers to the request.
	 */
	public function withCookieHeader(RequestInterface $request): RequestInterface
	{
		$values = [];
		$uri = $request->getUri();
		$scheme = $uri->getScheme();
		$host = $uri->getHost();
		$path = $uri->getPath() ?: '/';

		foreach ($this->cookies as $cookie) {
			if (
				$cookie->matchesPath($path) &&
				$cookie->matchesDomain($host) &&
				! $cookie->isExpired() &&
				(! $cookie->getSecure() || $scheme === 'https')
			) {
				$values[] = $cookie->getName() . '=' . $cookie->getValue();
			}
		}

		return $values
			? $request->withHeader('Cookie', implode('; ', $values))
			: $request;
	}
}
