<?php

namespace MVPS\Lumis\Framework\Http;

use Closure;
use Laminas\Diactoros\ServerRequest;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContent;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContentTypes;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithRequestInput;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;
use pdeans\Http\Factories\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\InputBag;

class Request extends ServerRequest
{
	use InteractsWithContent;
	use InteractsWithContentTypes;
	use InteractsWithRequestInput;

	/**
	 * The list of acceptable content types.
	 *
	 * @var array|null
	 */
	protected array|null $acceptableContentTypes = null;

	/**
	 * The request's root path.
	 */
	protected string|null $basePath = null;

	/**
	 * The request's root url.
	 */
	protected string|null $baseUrl = null;

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
	 * The decoded JSON content for the request.
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag|null
	 */
	protected InputBag|null $json = null;

	/**
	 * The input bag instance for the query string parameters.
	 *
	 * @var \Symfony\Component\HttpFoundation\InputBag
	 */
	public InputBag $queryBag;

	/**
	 * The input bag instance for the request parameters.
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

		$this->queryBag = new InputBag($this->getQueryParams());
		$this->requestBag = new InputBag((array) $this->getParsedBody());
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
		return $this->getUri();
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
		if (! isset($this->json)) {
			$this->json = new InputBag(
				(array) json_decode((string) $this->getBody() ?: '[]', true)
			);
		}

		if (is_null($key)) {
			return $this->json;
		}

		return data_get($this->json->all(), $key, $default);
	}

	/**
	 * Determine if the request is the result of a PJAX call.
	 */
	public function pjax(): bool
	{
		return $this->hasHeader('X-PJAX');
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
	 * Set the JSON payload for the request.
	 */
	public function setJson(InputBag $json): static
	{
		$this->json = $json;

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
		return Arr::get($this->input(), $key, fn () => $this->route($key));
	}
}
