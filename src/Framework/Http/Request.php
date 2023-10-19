<?php

namespace MVPS\Lumis\Framework\Http;

use Laminas\Diactoros\ServerRequest;
use pdeans\Http\Factories\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
	/**
	 * Create a modified server request from a PSR-7 server request instance.
	 */
	public static function capture(): ServerRequest
	{
		$request = ServerRequestFactory::fromGlobals();

		return new ServerRequest(
			serverParams: $request->getServerParams(),
			uploadedFiles: $request->getUploadedFiles(),
			uri: $request->getUri(),
			method: (new static)->getMethod($request),
			body: $request->getBody(),
			headers: $request->getHeaders(),
			cookieParams: $request->getCookieParams(),
			queryParams: $request->getQueryParams(),
			parsedBody: (new static)->getParsedBody($request)
		);
	}

	/**
	 * Get the intended server request method.
	 */
	public function getMethod(ServerRequestInterface $request): string
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
	public function getParsedBody(ServerRequestInterface $request): array
	{
		return $this->isJson($request->getHeader('content-type')[0] ?? '')
			? json_decode((string) $request->getBody(), true)
			: $request->getParsedBody();
	}

	/**
	 * Determine if the request is sending JSON.
	 *
	 * @return bool
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
}
