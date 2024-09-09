<?php

namespace MVPS\Lumis\Framework\Http\Client\Cookies;

class SetCookie
{
	/**
	 * Cookie data to be set.
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Default cookie parameters.
	 *
	 * @var array
	 */
	private static $defaults = [
		'Name' => null,
		'Value' => null,
		'Domain' => null,
		'Path' => '/',
		'Max-Age' => null,
		'Expires' => null,
		'Secure' => false,
		'Discard' => false,
		'HttpOnly' => false,
	];

	/**
	 * Create a new set cookie instance.
	 */
	public function __construct(array $data = [])
	{
		$this->data = self::$defaults;

		if (isset($data['Name'])) {
			$this->setName($data['Name']);
		}

		if (isset($data['Value'])) {
			$this->setValue($data['Value']);
		}

		if (isset($data['Domain'])) {
			$this->setDomain($data['Domain']);
		}

		if (isset($data['Path'])) {
			$this->setPath($data['Path']);
		}

		if (isset($data['Max-Age'])) {
			$this->setMaxAge($data['Max-Age']);
		}

		if (isset($data['Expires'])) {
			$this->setExpires($data['Expires']);
		}

		if (isset($data['Secure'])) {
			$this->setSecure($data['Secure']);
		}

		if (isset($data['Discard'])) {
			$this->setDiscard($data['Discard']);
		}

		if (isset($data['HttpOnly'])) {
			$this->setHttpOnly($data['HttpOnly']);
		}

		foreach (array_diff(array_keys($data), array_keys(self::$defaults)) as $key) {
			$this->data[$key] = $data[$key];
		}

		// Convert Expires value to UNIX timestamp for consistent handling.
		if (! $this->getExpires() && $this->getMaxAge()) {
			$this->setExpires(time() + $this->getMaxAge());
		} else {
			$expires = $this->getExpires();

			if (! is_null($expires) && ! is_numeric($expires)) {
				$this->setExpires($expires);
			}
		}
	}

	/**
	 * Create a new SetCookie object from a string.
	 */
	public static function fromString(string $cookie): static
	{
		$data = self::$defaults;

		// Parse the cookie string into an array of trimmed cookie parts,
		// removing empty elements.
		$pieces = array_filter(array_map('trim', explode(';', $cookie)));

		// The cookie name must be a valid key-value pair with an equal sign.
		if (! isset($pieces[0]) || strpos($pieces[0], '=') === false) {
			return new self($data);
		}

		foreach ($pieces as $part) {
			$cookieParts = explode('=', $part, 2);

			$key = trim($cookieParts[0]);

			$value = isset($cookieParts[1])
				? trim($cookieParts[1], " \n\r\t\0\x0B")
				: true;

			if (! isset($data['Name'])) {
				$data['Name'] = $key;
				$data['Value'] = $value;
			} else {
				foreach (array_keys(self::$defaults) as $search) {
					if (! strcasecmp($search, $key)) {
						if ($search === 'Max-Age') {
							if (is_numeric($value)) {
								$data[$search] = (int) $value;
							}
						} else {
							$data[$search] = $value;
						}

						continue 2;
					}
				}

				$data[$key] = $value;
			}
		}

		return new self($data);
	}

	/**
	 * Get whether or not this is a session cookie.
	 */
	public function getDiscard(): bool|null
	{
		return $this->data['Discard'];
	}

	/**
	 * Get the domain.
	 */
	public function getDomain(): string|null
	{
		return $this->data['Domain'];
	}

	/**
	 * The UNIX timestamp when the cookie Expires.
	 */
	public function getExpires(): string|int|null
	{
		return $this->data['Expires'];
	}

	/**
	 * Get whether or not this is an HTTP only cookie.
	 */
	public function getHttpOnly(): bool
	{
		return $this->data['HttpOnly'];
	}

	/**
	 * Maximum lifetime of the cookie in seconds.
	 */
	public function getMaxAge(): int|null
	{
		return is_null($this->data['Max-Age']) ? null : (int) $this->data['Max-Age'];
	}

	/**
	 * Get the cookie name.
	 */
	public function getName(): string
	{
		return $this->data['Name'];
	}

	/**
	 * Get the path.
	 */
	public function getPath(): string
	{
		return $this->data['Path'];
	}

	/**
	 * Get whether or not this is a secure cookie.
	 */
	public function getSecure(): bool
	{
		return $this->data['Secure'];
	}

	/**
	 * Get the cookie value.
	 */
	public function getValue(): string|null
	{
		return $this->data['Value'];
	}

	/**
	 * Check if the cookie is expired.
	 */
	public function isExpired(): bool
	{
		return ! is_null($this->getExpires()) && time() > $this->getExpires();
	}

	/**
	 * Check if the cookie matches a domain value.
	 */
	public function matchesDomain(string $domain): bool
	{
		$cookieDomain = $this->getDomain();

		if (is_null($cookieDomain)) {
			return true;
		}

		// Remove the leading '.' per RFC 6265 - https://datatracker.ietf.org/doc/html/rfc6265#section-5.2.3
		$cookieDomain = ltrim(strtolower($cookieDomain), '.');

		$domain = strtolower($domain);

		if ($cookieDomain === '' || $cookieDomain === $domain) {
			return true;
		}

		// Match the subdomain per RFC 6265 - https://datatracker.ietf.org/doc/html/rfc6265#section-5.1.3
		if (filter_var($domain, FILTER_VALIDATE_IP)) {
			return false;
		}

		return (bool) preg_match('/\.' . preg_quote($cookieDomain, '/') . '$/', $domain);
	}

	/**
	 * Determines if a cookie matches the request path.
	 *
	 * Checks if the cookie path is an exact or partial match for the request
	 * path, considering path separators and potential trailing slashes.
	 */
	public function matchesPath(string $requestPath): bool
	{
		$cookiePath = $this->getPath();

		if ($cookiePath === '/' || $cookiePath === $requestPath) {
			return true;
		}

		// Verify that the cookie path is a valid prefix of the request path.
		if (strpos($requestPath, $cookiePath) !== 0) {
			return false;
		}

		if (substr($cookiePath, -1, 1) === '/') {
			return true;
		}

		return substr($requestPath, strlen($cookiePath), 1) === '/';
	}

	/**
	 * Set whether or not this is a session cookie.
	 */
	public function setDiscard(bool $discard): void
	{
		$this->data['Discard'] = $discard;
	}

	/**
	 * Set the domain of the cookie.
	 */
	public function setDomain(string|null $domain = null): void
	{

		$this->data['Domain'] = is_null($domain) ? null : (string) $domain;
	}

	/**
	 * Set the unix timestamp for which the cookie will expire.
	 */
	public function setExpires(int|string|null $timestamp): void
	{
		$this->data['Expires'] = is_null($timestamp)
			? null
			: (is_numeric($timestamp) ? (int) $timestamp : strtotime((string) $timestamp));
	}

	/**
	 * Set whether or not this is an HTTP only cookie.
	 */
	public function setHttpOnly(bool $httpOnly): void
	{
		$this->data['HttpOnly'] = $httpOnly;
	}

	/**
	 * Set the max-age of the cookie.
	 */
	public function setMaxAge(int|null $maxAge): void
	{
		$this->data['Max-Age'] = is_null($maxAge) ? null : (int) $maxAge;
	}

	/**
	 * Set the cookie name.
	 */
	public function setName(string $name): void
	{
		$this->data['Name'] = $name;
	}

	/**
	 * Set the path of the cookie.
	 */
	public function setPath(string $path): void
	{
		$this->data['Path'] = $path;
	}

	/**
	 * Set whether or not the cookie is secure.
	 */
	public function setSecure(bool $secure): void
	{
		$this->data['Secure'] = $secure;
	}

	/**
	 * Set the cookie value.
	 */
	public function setValue(string $value): void
	{
		$this->data['Value'] = $value;
	}

	public function toArray(): array
	{
		return $this->data;
	}

	/**
	 * Check if the cookie is valid according to RFC 6265.
	 */
	public function validate(): bool|string
	{
		$name = $this->getName();

		if ($name === '') {
			return 'The cookie name must not be empty';
		}

		// Validate cookie name for invalid characters.
		if (preg_match('/[\x00-\x20\x22\x28-\x29\x2c\x2f\x3a-\x40\x5c\x7b\x7d\x7f]/', $name)) {
			return 'Cookie name must not contain invalid characters: ASCII '
				. 'Control characters (0-31;127), space, tab and the '
				. 'following characters: ()<>@,;:\"/?={}';
		}

		// Value cannot be null. Empty strings and numeric 0 are accepted,
		// although empty strings are technically against RFC 6265.
		$value = $this->getValue();

		if (is_null($value)) {
			return 'The cookie value must not be empty';
		}

		// Domain values cannot be empty, but can be "0". While "0" is not a
		// valid internet domain, it can be used for server names in private
		// networks.
		$domain = $this->getDomain();

		if (is_null($domain) || $domain === '') {
			return 'The cookie domain must not be empty';
		}

		return true;
	}

	/**
	 * Converts the cookie data into a string representation suitable for
	 * setting a cookie header.
	 */
	public function __toString(): string
	{
		$str = $this->data['Name'] . '=' . ($this->data['Value'] ?? '') . '; ';

		foreach ($this->data as $k => $v) {
			if ($k !== 'Name' && $k !== 'Value' && ! is_null($v) && $v !== false) {
				if ($k === 'Expires') {
					$str .= 'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', $v) . '; ';
				} else {
					$str .= ($v === true ? $k : "{$k}={$v}") . '; ';
				}
			}
		}

		return rtrim($str, '; ');
	}
}
