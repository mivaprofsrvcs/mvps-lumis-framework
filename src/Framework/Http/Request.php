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
	 * Get the route resolver callback.
	 */
	public function getRouteResolver(): Closure
	{
		return $this->routeResolver ?: function () {
		};
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

	/**
	 * Set the route resolver callback.
	 */
	public function setRouteResolver(Closure $callback): static
	{
		$this->routeResolver = $callback;

		return $this;
	}
}
