<?php

namespace MVPS\Lumis\Framework\Http;

use Closure;
use Laminas\Diactoros\ServerRequest;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithRequestInput;
use pdeans\Http\Factories\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest
{
	use InteractsWithRequestInput;

	/**
	 * The request's root path.
	 */
	protected string|null $basePath = null;

	/**
	 * The request's root url.
	 */
	protected string|null $baseUrl = null;

	/**
	 * The route resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $routeResolver = null;

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
	 * Get the full URL for the request.
	 */
	public function getFullUrl(): string
	{
		return $this->getUri();
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
	 * Get the parsed body data from a server request.
	 */
	public static function getParsedBodyFromRequest(ServerRequestInterface $request): array
	{
		return (new static)->isJson($request->getHeader('content-type')[0] ?? '')
			? json_decode((string) $request->getBody(), true)
			: $request->getParsedBody();
	}

	/**
	 * Get the current URI path for the request.
	 */
	public function getPath(): string
	{
		return $this->getUri()->getPath();
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
	 * Determine if the request is sending JSON.
	 */
	public function isJson(string $contentType): bool
	{
		if ($contentType === '') {
			return false;
		}

		foreach (['/json', '+json'] as $jsonContentCheck) {
			if (str_contains($contentType, $jsonContentCheck)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the request method matches the given type.
	 */
	public function isMethod(string $method): bool
	{
		return $this->getMethod() === strtoupper($method);
	}

	public function isSecure(): bool
	{
		return $this->getUri()->getScheme() === 'https';
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
	 * Set the route resolver callback.
	 */
	public function setRouteResolver(Closure $callback): static
	{
		$this->routeResolver = $callback;

		return $this;
	}
}
