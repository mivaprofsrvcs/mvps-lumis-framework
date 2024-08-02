<?php

namespace MVPS\Lumis\Framework\Http;

use ArrayAccess;
use Closure;
use Laminas\Diactoros\ServerRequest;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContent;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContentTypes;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithRequestInput;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use pdeans\Http\Factories\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ServerBag;

class Request extends ServerRequest implements Arrayable, ArrayAccess
{
	use InteractsWithContent;
	use InteractsWithContentTypes;
	use InteractsWithRequestInput;

	/**
	 * Bitmask for the `FORWARDED` header (RFC 7239 standard).
	 */
	public const HEADER_FORWARDED = 0b000001;

	/**
	 * Bitmask for the `X_FORWARDED_FOR` header.
	 */
	public const HEADER_X_FORWARDED_FOR = 0b000010;

	/**
	 * Bitmask for the `X_FORWARDED_HOST` header.
	 */
	public const HEADER_X_FORWARDED_HOST = 0b000100;

	/**
	 * Bitmask for the `X_FORWARDED_PROTO` header.
	 */
	public const HEADER_X_FORWARDED_PROTO = 0b001000;

	/**
	 * Bitmask for the `X_FORWARDED_PORT` header.
	 */
	public const HEADER_X_FORWARDED_PORT = 0b010000;

	/**
	 * Bitmask for the `X_FORWARDED_PREFIX` header.
	 */
	public const HEADER_X_FORWARDED_PREFIX = 0b100000;

	/**
	 * Bitmask for headers sent by AWS ELB (excluding X-Forwarded-Host).
	 */
	public const HEADER_X_FORWARDED_AWS_ELB = 0b0011010;

	/**
	 * Bitmask for all "X-Forwarded-*" headers sent by Traefik reverse proxy.
	 */
	public const HEADER_X_FORWARDED_TRAEFIK = 0b0111110;

	/**
	 * Trusted headers for proxy information.
	 *
	 * Contains standard and commonly used headers for extracting proxy
	 * information.
	 *
	 * @var array<string, string>
	 */
	protected const TRUSTED_HEADERS = [
		self::HEADER_FORWARDED => 'FORWARDED',
		self::HEADER_X_FORWARDED_FOR => 'X_FORWARDED_FOR',
		self::HEADER_X_FORWARDED_HOST => 'X_FORWARDED_HOST',
		self::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
		self::HEADER_X_FORWARDED_PORT => 'X_FORWARDED_PORT',
		self::HEADER_X_FORWARDED_PREFIX => 'X_FORWARDED_PREFIX',
	];

	/**
	 * Mapping of trusted headers to their corresponding parameter names.
	 *
	 * @var array<string, string>
	 */
	protected const FORWARDED_PARAMS = [
		self::HEADER_X_FORWARDED_FOR => 'for',
		self::HEADER_X_FORWARDED_HOST => 'host',
		self::HEADER_X_FORWARDED_PROTO => 'proto',
		self::HEADER_X_FORWARDED_PORT => 'host',
	];

	/**
	 * The list of acceptable content types.
	 *
	 * @var array|null
	 */
	protected array|null $acceptableContentTypes = null;

	/**
	 * The parameter bag instance for the custom parameters.
	 *
	 * @var \Symfony\Component\HttpFoundation\ParameterBag
	 */
	public ParameterBag $attributeBag;

	/**
	 * The request's root path.
	 */
	protected string|null $basePath = null;

	/**
	 * The request's root url.
	 */
	protected string|null $baseUrl = null;

	/**
	 * All of the converted files for the request.
	 *
	 * @var array|null
	 */
	protected array|null $convertedFiles = null;

	/**
	 * The input bag instance for the cookies ($_COOKIE).
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag
	 */
	public InputBag $cookieBag;

	/**
	 * The file bag for the uploaded files ($_FILES).
	 *
	 * @var \Symfony\Component\HttpFoundation\FileBag
	 */
	public FileBag $fileBag;

	/**
	 * The request format MIME types.
	 *
	 * @var array
	 */
	protected static array $formats = [
		'atom' => ['application/atom+xml'],
		'css' => ['text/css'],
		'form' => ['application/x-www-form-urlencoded', 'multipart/form-data'],
		'html' => ['text/html', 'application/xhtml+xml'],
		'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
		'json' => ['application/json', 'application/x-json'],
		'jsonld' => ['application/ld+json'],
		'rdf' => ['application/rdf+xml'],
		'rss' => ['application/rss+xml'],
		'txt' => ['text/plain'],
		'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
	];

	/**
	 * The header bag instance for the headers.
	 *
	 * @var \Symfony\Component\HttpFoundation\HeaderBag
	 */
	public HeaderBag $headerBag;

	/**
	 * Indicates whether the forwarded headers are valid.
	 *
	 * @var bool
	 */
	protected bool $isForwardedValid = true;

	/**
	 * The input bag instance for the decoded JSON content.
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag|null
	 */
	protected InputBag|null $jsonBag = null;

	/**
	 * The input bag instance for the query string parameters ($_GET).
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag
	 */
	public InputBag $queryBag;

	/**
	 * The input bag instance for the request parameters ($_POST).
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag
	 */
	public InputBag $requestBag;

	/**
	 * The route resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $routeResolver = null;

	/**
	 * The server bag instance for the server parameters ($_SERVER).
	 *
	 * @var \Symfony\Component\HttpFoundation\ServerBag
	 */
	public ServerBag $serverBag;

	/**
	 * Cache for trusted header values.
	 *
	 * @var array
	 */
	protected array $trustedValuesCache = [];

	/**
	 * A flag indicating whether the trusted headers have been set.
	 *
	 * @var int
	 */
	protected static int $trustedHeaderSet = -1;

	/**
	 * A list of trusted proxy IP addresses or subnets.
	 *
	 * @var array<string>
	 */
	protected static array $trustedProxies = [];

	public function __construct(
		array $serverParams = [],
		array $uploadedFiles = [],
		null|string|UriInterface $uri = null,
		string|null $method = null,
		$body = 'php://input',
		array $headers = [],
		array $cookieParams = [],
		array $queryParams = [],
		$parsedBody = null,
		string $protocol = '1.1'
	) {
		parent::__construct(
			serverParams: $serverParams,
			uploadedFiles: $uploadedFiles,
			uri: $uri,
			method: $method,
			body: $body,
			headers: $headers,
			cookieParams: $cookieParams,
			queryParams: $queryParams,
			parsedBody: $parsedBody,
			protocol: $protocol
		);

		$this->requestBag = new InputBag((array) $this->getParsedBody());
		$this->queryBag = new InputBag($this->getQueryParams());
		$this->attributeBag = new ParameterBag($this->getAttributes());
		$this->cookieBag = new InputBag($this->getCookieParams());
		$this->fileBag = new FileBag($this->getUploadedFiles());
		$this->serverBag = new ServerBag($this->getServerParams());
		$this->headerBag = new HeaderBag($this->serverBag->getHeaders());
	}

	/**
	 * Determine if the request is the result of an AJAX call.
	 */
	public function ajax(): bool
	{
		return 'XMLHttpRequest' === $this->header('X-Requested-With');
	}

	/**
	 * Create a modified server request from a PSR-7 server request instance.
	 */
	public static function capture(): static
	{
		$request = ServerRequestFactory::fromGlobals();

		return new static(
			serverParams: $request->getServerParams(),
			uploadedFiles: $request->getUploadedFiles(),
			uri: $request->getUri(),
			method: static::getMethodFromRequest($request),
			body: $request->getBody(),
			headers: $request->getHeaders(),
			cookieParams: $request->getCookieParams(),
			queryParams: $request->getQueryParams(),
			parsedBody: static::getParsedBodyFromRequest($request)
		);
	}

	/**
	 * Get the current decoded path info for the request.
	 */
	public function decodedPath(): string
	{
		return rawurldecode($this->getPath());
	}

	/**
	 * Filter the given array of files, removing any empty values.
	 */
	protected function filterFiles(mixed $files): mixed
	{
		if (! $files) {
			return null;
		}

		foreach ($files as $key => $file) {
			if (is_array($file)) {
				$files[$key] = $this->filterFiles($files[$key]);
			}

			if (empty($files[$key])) {
				unset($files[$key]);
			}
		}

		return $files;
	}

	/**
	 * Generates a unique fingerprint for the request based on
	 * route, IP address, and HTTP methods.
	 *
	 * @throws \RuntimeException
	 */
	public function fingerprint(): string
	{
		$route = $this->route();

		if (! $route) {
			throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
		}

		return sha1(implode('|', array_merge(
			$route->methods(),
			[$route->getDomain(), $route->uri(), $this->ip()]
		)));
	}

	/**
	 * Determine if the current request URL and query string match a pattern.
	 */
	public function fullUrlIs(mixed ...$patterns): bool
	{
		$url = $this->getFullUrl();

		return collection($patterns)
			->contains(fn ($pattern) => Str::is($pattern, $url));
	}

	 /**
	 * Gets a list of content types acceptable by the client browser in preferable order.
	 */
	public function getAcceptableContentTypes(): array
	{
		return $this->acceptableContentTypes ??= array_map(
			'strval',
			array_keys(AcceptHeader::fromString($this->header('Accept', null))->all())
		);
	}

	/**
	 * Returns the root path (not urldecoded) from which this request is executed.
	 *
	 * Suppose that an index.php file instantiates this request object:
	 *
	 *  * http://localhost/index.php         returns an empty string
	 *  * http://localhost/index.php/page    returns an empty string
	 *  * http://localhost/web/index.php     returns '/web'
	 *  * http://localhost/we%20b/index.php  returns '/we%20b'
	 */
	public function getBasePath(): string
	{
		return $this->basePath ??= $this->prepareBasePath();
	}

	/**
	 * Returns the root URL (not urldecoded) from which this request is executed.
	 *
	 * The base URL never ends with a /.
	 *
	 * This is similar to getBasePath(), except that it also includes the
	 * script filename (e.g. index.php) if one exists.
	 */
	public function getBaseUrl(): string
	{
		return $this->baseUrl ??= $this->prepareBaseUrl();
	}

	/**
	 * Gets the format associated with the mime type.
	 */
	public function getFormat(string|null $mimeType = null): string|null
	{
		$canonicalMimeType = null;

		$pos = strpos($mimeType, ';');

		if ($mimeType && $pos !== false) {
			$canonicalMimeType = trim(substr($mimeType, 0, $pos));
		}

		foreach (static::$formats as $format => $mimeTypes) {
			if (in_array($mimeType, (array) $mimeTypes, true)) {
				return $format;
			}

			if (is_null($canonicalMimeType) && in_array($canonicalMimeType, (array) $mimeTypes, true)) {
				return $format;
			}
		}

		return null;
	}

	/**
	 * Get the full URL for the request.
	 */
	public function getFullUrl(): string
	{
		return (string) $this->getUri();
	}

	/**
	 * Get the full URL for the request with the added query string parameters.
	 */
	public function getFullUrlWithQuery(array $query): string
	{
		$separator = $this->getBaseUrl() . $this->getUri()->getPath() === '/' ? '/?' : '?';
		$queryParams = $this->query();

		return count($queryParams) > 0
			? $this->getUrl() . $separator . Arr::query(array_merge($queryParams, $query))
			: $this->getFullUrl() . $separator . Arr::query($query);
	}

	public function getFullUrlWithoutQuery(array|string $keys): string
	{
		$query = Arr::except($this->query(), $keys);

		$separator = $this->getBaseUrl() . $this->getUri()->getPath() === '/' ? '/?' : '?';

		return count($query) > 0
			? $this->getUrl() . $separator . Arr::query($query)
			: $this->getUrl();
	}

	/**
	 * Get the request host name.
	 */
	public function getHost(): string
	{
		return $this->getUri()->getHost();
	}

	/**
	 * Get the HTTP host being requested.
	 */
	public function getHttpHost(): string
	{
		return $this->getUri()->getAuthority();
	}

	/**
	 * Get the input source for the request.
	 */
	protected function getInputSource(): InputBag
	{
		if ($this->isJson()) {
			return $this->json();
		}

		return in_array($this->getRealMethod(), ['GET', 'HEAD'])
			? $this->queryBag
			: $this->requestBag;
	}

	/**
	 * Get the intended server request method.
	 */
	public static function getMethodFromRequest(ServerRequestInterface $request): string
	{
		$method = $request->getMethod();

		if ($method !== 'POST') {
			return $method;
		}

		$body = $request->getParsedBody();

		if (empty($body['_method']) || ! is_string(($body['_method']))) {
			return $method;
		}

		$validMethods = [
			'GET',
			'POST',
			'DELETE',
			'PUT',
			'PATCH',
			'HEAD',
			'OPTIONS',
			'CONNECT',
			'PURGE',
			'TRACE',
		];

		$bodyMethod = strtoupper($body['_method']);

		if (in_array($bodyMethod, $validMethods, true)) {
			$method = $bodyMethod;
		}

		return $method;
	}

	/**
	 * Gets the mime type associated with the format.
	 */
	public function getMimeType(string $format): string|null
	{
		return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
	}

	/**
	 * Gets the mime types associated with the format.
	 */
	public static function getMimeTypes(string $format): array
	{
		return static::$formats[$format] ?? [];
	}

	/**
	 * Get the parsed body data from a server request.
	 */
	public static function getParsedBodyFromRequest(ServerRequestInterface $request): array
	{
		return $request->hasHeader('Content-Type') && str_contains($request->getHeader('Content-Type')[0], 'json')
			? json_decode((string) $request->getBody(), true)
			: $request->getParsedBody();
	}

	/**
	 * Get the current URI path for the request.
	 */
	public function getPath(): string
	{
		$path = trim($this->getUri()->getPath(), '/');

		return $path === '' ? '/' : $path;
	}

	/**
	 * Get the current URI port for the request.
	 */
	public function getPort(): int|null
	{
		return $this->getUri()->getPort();
	}

	/**
	 * Gets the real request method.
	 */
	public function getRealMethod(): string
	{
		return strtoupper($this->serverBag->get('REQUEST_METHOD', 'GET'));
	}

	/**
	 * Get the root URL for the application.
	 */
	public function getRoot(): string
	{
		return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
	}

	/**
	 * Get the route resolver callback.
	 */
	public function getRouteResolver(): Closure
	{
		return $this->routeResolver ?: function () {
		};
	}

	/**
	 * Get the request scheme.
	 */
	public function getScheme(): string
	{
		return $this->getUri()->getScheme();
	}

	/**
	 * Get the request scheme and HTTP host.
	 */
	public function getSchemeAndHttpHost(): string
	{
		return $this->getUri()->getScheme() . '://' . $this->getHttpHost();
	}

	/**
	 * Gets the set of trusted headers from trusted proxies.
	 */
	public static function getTrustedHeaderSet(): int
	{
		return self::$trustedHeaderSet;
	}

	/**
	 * Gets the list of trusted proxies.
	 */
	public static function getTrustedProxies(): array
	{
		return self::$trustedProxies;
	}

	/**
	 * Retrieves trusted values from request headers.
	 *
	 * Caches results for performance optimization. Handles header parsing,
	 * validation, and normalization. Detects and handles conflicting forwarded
	 * headers.
	 *
	 * @throws \Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException
	 */
	protected function getTrustedValues(int $type, string|null $ip = null): array
	{
		$cacheKey = $type . "\0" . (
			(static::$trustedHeaderSet & $type)
				? $this->headerBag->get(static::TRUSTED_HEADERS[$type])
				: ''
		);

		$cacheKey .= "\0" . $ip . "\0" . $this->headerBag->get(static::TRUSTED_HEADERS[static::HEADER_FORWARDED]);

		if (isset($this->trustedValuesCache[$cacheKey])) {
			return $this->trustedValuesCache[$cacheKey];
		}

		$clientValues = [];
		$forwardedValues = [];

		if ((static::$trustedHeaderSet & $type) && $this->headerBag->has(static::TRUSTED_HEADERS[$type])) {
			foreach (explode(',', $this->headerBag->get(static::TRUSTED_HEADERS[$type])) as $val) {
				$clientValues[] = (static::HEADER_X_FORWARDED_PORT === $type ? '0.0.0.0:' : '') . trim($val);
			}
		}

		if (
			(static::$trustedHeaderSet & static::HEADER_FORWARDED) &&
			(isset(static::FORWARDED_PARAMS[$type])) &&
			$this->headerBag->has(static::TRUSTED_HEADERS[static::HEADER_FORWARDED])
		) {
			$forwarded = $this->headerBag->get(static::TRUSTED_HEADERS[static::HEADER_FORWARDED]);

			$parts = HeaderUtils::split($forwarded, ',;=');

			$param = static::FORWARDED_PARAMS[$type];

			foreach ($parts as $subParts) {
				$val = HeaderUtils::combine($subParts)[$param] ?? null;

				if (is_null($val)) {
					continue;
				}

				if (static::HEADER_X_FORWARDED_PORT === $type) {
					$val = strrchr($val, ':');

					if (str_ends_with($val, ']') || $val === false) {
						$val = $this->isSecure() ? ':443' : ':80';
					}

					$val = '0.0.0.0' . $val;
				}

				$forwardedValues[] = $val;
			}
		}

		if (! is_null($ip)) {
			$clientValues = $this->normalizeAndFilterClientIps($clientValues, $ip);
			$forwardedValues = $this->normalizeAndFilterClientIps($forwardedValues, $ip);
		}

		if ($forwardedValues === $clientValues || ! $clientValues) {
			return $this->trustedValuesCache[$cacheKey] = $forwardedValues;
		}

		if (! $forwardedValues) {
			return $this->trustedValuesCache[$cacheKey] = $clientValues;
		}

		if (!$this->isForwardedValid) {
			return $this->trustedValuesCache[$cacheKey] = ! is_null($ip) ? ['0.0.0.0', $ip] : [];
		}

		$this->isForwardedValid = false;

		throw new ConflictingHeadersException(sprintf(
			'The request has both a trusted "%s" header and a trusted "%s" header, conflicting with each other.' .
				' You should either configure your proxy to remove one of them,' .
				' or configure your project to distrust the offending one.',
			static::TRUSTED_HEADERS[static::HEADER_FORWARDED],
			static::TRUSTED_HEADERS[$type]
		));
	}

	/**
	 * Get the full URL for the request without the query string (if present).
	 */
	public function getUrl(): string
	{
		return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
	}

	/**
	 * Returns the prefix as encoded in the string when the string starts with
	 * the given prefix, null otherwise.
	 */
	private function getUrlencodedPrefix(string $string, string $prefix): string|null
	{
		if (! str_starts_with(rawurldecode($string), $prefix)) {
			return null;
		}

		$len = strlen($prefix);

		if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
			return $match[0];
		}

		return null;
	}

	/**
	 * Return the Request instance.
	 */
	public function instance(): static
	{
		return $this;
	}

	/**
	 * Get the client IP address.
	 */
	public function ip(): string|null
	{
		return $this->ips()[0] ?? null;
	}

	/**
	 * Get the client IP addresses.
	 */
	public function ips(): array
	{
		$ip = $this->serverBag->get('REMOTE_ADDR');

		if (! $this->isFromTrustedProxy()) {
			return [$ip];
		}

		return $this->getTrustedValues(static::HEADER_X_FORWARDED_FOR, $ip) ?: [$ip];
	}

	/**
	 * Indicates whether this request originated from a trusted proxy.
	 *
	 * This can be useful to determine whether or not to trust the
	 * contents of a proxy-specific header.
	 */
	public function isFromTrustedProxy(): bool
	{
		return static::$trustedProxies &&
			IpUtils::checkIp($this->serverBag->get('REMOTE_ADDR', ''), static::$trustedProxies);
	}

	/**
	 * Determine if the current request URI matches a pattern.
	 */
	public function is(mixed ...$patterns): bool
	{
		$path = $this->decodedPath();

		return collection($patterns)
			->contains(fn ($pattern) => Str::is($pattern, $path));
	}

	/**
	 * Checks if the request method matches the given type.
	 */
	public function isMethod(string $method): bool
	{
		return $this->getMethod() === strtoupper($method);
	}

	/**
	 * Determine if the current request is secure (https).
	 */
	public function isSecure(): bool
	{
		return $this->getUri()->getScheme() === 'https';
	}

	/**
	 * Get the JSON payload for the request.
	 */
	public function json(string|null $key = null, mixed $default = null): mixed
	{
		if (is_null($this->jsonBag)) {
			$this->jsonBag = new InputBag(
				(array) json_decode((string) $this->getBody() ?: '[]', true)
			);
		}

		if (is_null($key)) {
			return $this->jsonBag;
		}

		return data_get($this->jsonBag->all(), $key, $default);
	}

	/**
	 * Merge new input into the current request's input array.
	 */
	public function merge(array $input): static
	{
		$this->getInputSource()->add($input);

		return $this;
	}

	/**
	 * Merges new input into the request's input, preserving existing values.
	 *
	 * Only adds input values that are missing from the current request.
	 */
	public function mergeIfMissing(array $input): static
	{
		return $this->merge(
			collection($input)->filter(fn ($value, $key) => $this->missing($key))
				->toArray()
		);
	}

	/**
	 * Normalizes and filters client IP addresses.
	 *
	 * Strips ports from IPv4 and IPv6 addresses, validates IP format,
	 * and filters out trusted proxies. Returns a reversed array of valid client
	 * IP addresses, or the first trusted IP if no valid addresses are found.
	 */
	protected function normalizeAndFilterClientIps(array $clientIps, string $ip): array
	{
		if (! $clientIps) {
			return [];
		}

		$clientIps[] = $ip;
		$firstTrustedIp = null;

		foreach ($clientIps as $key => $clientIp) {
			if (strpos($clientIp, '.')) {
				// Remove port number from IPv4 addresses. This is allowed in
				// the Forwarded header and may occur in X-Forwarded-For.
				$portIndex = strpos($clientIp, ':');

				if ($portIndex) {
					$clientIps[$key] = $clientIp = substr($clientIp, 0, $portIndex);
				}
			} elseif (str_starts_with($clientIp, '[')) {
				// Remove brackets and port from IPv6 address.
				$endBracketIndex = strpos($clientIp, ']', 1);

				$clientIps[$key] = $clientIp = substr($clientIp, 1, $endBracketIndex - 1);
			}

			if (! filter_var($clientIp, FILTER_VALIDATE_IP)) {
				unset($clientIps[$key]);

				continue;
			}

			if (IpUtils::checkIp($clientIp, static::$trustedProxies)) {
				unset($clientIps[$key]);

				// Use the first trusted proxy IP as a fallback if no valid
				// client IPs are found.
				$firstTrustedIp ??= $clientIp;
			}
		}

		return $clientIps ? array_reverse($clientIps) : [$firstTrustedIp];
	}

	/**
	 * Determine if the given offset exists.
	 */
	public function offsetExists(mixed $offset): bool
	{
		$route = $this->route();

		return Arr::has(
			$this->all() + ($route ? $route->parameters() : []),
			$offset
		);
	}

	/**
	 * Get the value at the given offset.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->__get($offset);
	}

	/**
	 * Set the value at the given offset.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->getInputSource()->set($offset, $value);
	}

	/**
	 * Remove the value at the given offset.
	 */
	public function offsetUnset(mixed $offset): void
	{
		$this->getInputSource()->remove($offset);
	}

	/**
	 * Determine if the request is the result of a PJAX call.
	 */
	public function pjax(): bool
	{
		return $this->hasHeader('X-PJAX');
	}

	/**
	 * Determine if the request is the result of a prefetch call.
	 */
	public function prefetch(): bool
	{
		return strcasecmp($this->serverBag->get('HTTP_X_MOZ') ?? '', 'prefetch') === 0 ||
			strcasecmp($this->headerBag->get('Purpose') ?? '', 'prefetch') === 0 ||
			strcasecmp($this->headerBag->get('Sec-Purpose') ?? '', 'prefetch') === 0;
	}

	/**
	 * Prepares the base path.
	 */
	protected function prepareBasePath(): string
	{
		$baseUrl = $this->getBaseUrl();

		if (!$baseUrl) {
			return '';
		}

		$filename = basename($this->getServerParams()['SCRIPT_FILENAME'] ?? '');

		if (basename($baseUrl) === $filename) {
			$basePath = dirname($baseUrl);
		} else {
			$basePath = $baseUrl;
		}

		if ('\\' === DIRECTORY_SEPARATOR) {
			$basePath = str_replace('\\', '/', $basePath);
		}

		return rtrim($basePath, '/');
	}

	/**
	 * Prepares the base URL.
	 */
	protected function prepareBaseUrl(): string
	{
		$serverParams = $this->getServerParams();
		$filename = basename($serverParams['SCRIPT_FILENAME'] ?? '');

		if (basename($serverParams['SCRIPT_NAME'] ?? '') === $filename) {
			$baseUrl = $serverParams['SCRIPT_NAME'] ?? '';
		} elseif (basename($serverParams['PHP_SELF'] ?? '') === $filename) {
			$baseUrl = $serverParams['PHP_SELF'] ?? '';
		} elseif (basename($serverParams['ORIG_SCRIPT_NAME'] ?? '') === $filename) {
			$baseUrl = $serverParams['ORIG_SCRIPT_NAME'] ?? '';
		} else {
			// Backtrack up the script_filename to find the portion matching
			$path = $serverParams['PHP_SELF'] ?? '';
			$file = $serverParams['SCRIPT_FILENAME'] ?? '';
			$segs = array_reverse((explode('/', trim($file, '/'))));
			$index = 0;
			$last = count($segs);
			$baseUrl = '';

			do {
				$seg = $segs[$index];
				$baseUrl = '/' . $seg . $baseUrl;
				++$index;
			} while ($last > $index && (false !== $pos = strpos($path, $baseUrl)) && 0 != $pos);
		}

		// Does the baseUrl have anything in common with the request_uri?
		$requestUri = $this->getUri()->getPath();

		if ('' !== $requestUri && '/' !== $requestUri[0]) {
			$requestUri = '/' . $requestUri;
		}

		$prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl);

		if ($baseUrl && ! is_null($prefix)) {
			return $prefix;
		}

		$prefix = $this->getUrlencodedPrefix($requestUri, rtrim(dirname($baseUrl), '/' . DIRECTORY_SEPARATOR) . '/');

		if ($baseUrl && ! is_null($prefix)) {
			return rtrim($prefix, '/' . DIRECTORY_SEPARATOR);
		}

		$truncatedRequestUri = $requestUri;
		$pos = strpos($requestUri, '?');

		if ($pos !== false) {
			$truncatedRequestUri = substr($requestUri, 0, $pos);
		}

		$basename = basename($baseUrl ?? '');

		if (! $basename || ! strpos(rawurldecode($truncatedRequestUri), $basename)) {
			return '';
		}

		// If using mod_rewrite or ISAPI_Rewrite strip the script filename
		// out of baseUrl. $pos !== 0 makes sure it is not matching a value
		// from PATH_INFO or QUERY_STRING.
		$pos = strpos($requestUri, $baseUrl);

		if (strlen($requestUri) >= strlen($baseUrl) && $pos !== false && $pos !== 0) {
			$baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
		}

		return rtrim($baseUrl, '/' . DIRECTORY_SEPARATOR);
	}

	/**
	 * Replace the input values for the current request.
	 */
	public function replace(array $input): static
	{
		$this->getInputSource()->replace($input);

		return $this;
	}

	/**
	 * Get the route handling the request.
	 */
	public function route(string|null $param = null, mixed $default = null): Route|string|null
	{
		$route = call_user_func($this->getRouteResolver());

		if (is_null($route) || is_null($param)) {
			return $route;
		}

		return $route->parameter($param, $default);
	}

	/**
	 * Determine if the route name matches a given pattern.
	 */
	public function routeIs(mixed ...$patterns): bool
	{
		$route = $this->route();

		return $route && $route->named(...$patterns);
	}

	/**
	 * Get a segment from the URI (1 based index).
	 */
	public function segment(int $index, string|null $default = null): string|null
	{
		return Arr::get($this->segments(), $index - 1, $default);
	}

	/**
	 * Get all of the segments for the request path.
	 */
	public function segments(): array
	{
		$segments = explode('/', $this->decodedPath());

		return array_values(
			array_filter($segments, fn ($value) => $value !== '')
		);
	}

	/**
	 * Set the JSON payload for the request.
	 */
	public function setJson(InputBag $json): static
	{
		$this->jsonBag = $json;

		return $this;
	}

	/**
	 * Set the route resolver callback.
	 */
	public function setRouteResolver(Closure $callback): static
	{
		$this->routeResolver = $callback;

		return $this;
	}

	/**
	 * Sets a list of trusted proxies.
	 *
	 * You should only list the reverse proxies that you manage directly.
	 */
	public static function setTrustedProxies(array $proxies, int $trustedHeaderSet): void
	{
		static::$trustedProxies = array_reduce($proxies, function ($proxies, $proxy) {
			if ('REMOTE_ADDR' !== $proxy) {
				$proxies[] = $proxy;
			} elseif (isset($_SERVER['REMOTE_ADDR'])) {
				$proxies[] = $_SERVER['REMOTE_ADDR'];
			}

			return $proxies;
		}, []);

		static::$trustedHeaderSet = $trustedHeaderSet;
	}

	/**
	 * Get all of the input and files for the request.
	 */
	public function toArray(): array
	{
		return $this->all();
	}

	/**
	 * Get the client user agent.
	 */
	public function userAgent(): string|null
	{
		return $this->headerBag->get('User-Agent');
	}

	/**
	 * Check if an input element is set on the request.
	 */
	public function __isset(string $key): bool
	{
		return ! is_null($this->__get($key));
	}

	/**
	 * Get an input element from the request.
	 */
	public function __get(string $key): mixed
	{
		return Arr::get($this->all(), $key, fn () => $this->route($key));
	}
}
