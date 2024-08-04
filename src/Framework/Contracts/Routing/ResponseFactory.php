<?php

namespace MVPS\Lumis\Framework\Contracts\Routing;

use MVPS\Lumis\Framework\Http\BinaryFileResponse;
use MVPS\Lumis\Framework\Http\JsonResponse;
use MVPS\Lumis\Framework\Http\Response;
use SplFileInfo;

interface ResponseFactory
{
	/**
	 * Create a new file download response.
	 */
	public function download(
		SplFileInfo|string $file,
		string|null $name = null,
		array $headers = [],
		string|null $disposition = 'attachment'
	): BinaryFileResponse;

	/**
	 * Return the raw contents of a binary file.
	 */
	public function file(SplFileInfo|string $file, array $headers = []): BinaryFileResponse;

	/**
	 * Create a new JSON response instance.
	 */
	public function json(mixed $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse;

	/**
	 * Create a new JSONP response instance.
	 */
	public function jsonp(
		string $callback,
		mixed $data = [],
		int $status = 200,
		array $headers = [],
		int $options = 0
	): JsonResponse;

	/**
	 * Create a new response instance.
	 */
	public function make(mixed $content = '', int $status = 200, array $headers = []): Response;

	/**
	 * Create a new "no content" response.
	 */
	public function noContent(int $status = 204, array $headers = []): Response;

	/**
	 * Create a new redirect response, while putting the current URL in the session.
	 */
	// public function redirectGuest(
	// 	string $path,
	// 	int $status = 302,
	// 	array $headers = [],
	// 	bool|null $secure = null
	// ): RedirectResponse;

	/**
	 * Create a new redirect response to the given path.
	 */
	// public function redirectTo(
	// 	string $path,
	// 	int $status = 302,
	// 	array $headers = [],
	// 	bool|null $secure = null
	// ): RedirectResponse;

	/**
	 * Create a new redirect response to a controller action.
	 */
	// public function redirectToAction(
	// 	array|string $action,
	// 	mixed $parameters = [],
	// 	int $status = 302,
	// 	array $headers = []
	// ): RedirectResponse;

	/**
	 * Create a new redirect response to the previously intended location.
	 */
	// public function redirectToIntended(
	// 	string $default = '/',
	// 	int $status = 302,
	// 	array $headers = [],
	// 	bool|null $secure = null
	// ): RedirectResponse;

	/**
	 * Create a new redirect response to a named route.
	 */
	// public function redirectToRoute(
	// 	string $route,
	// 	mixed $parameters = [],
	// 	int $status = 302,
	// 	array $headers = []
	// ): RedirectResponse;

	/**
	 * Create a new streamed response instance.
	 */
	// public function stream(callable $callback, int $status = 200, array $headers = []): StreamedResponse;

	/**
	 * Create a new streamed response instance as a file download.
	 */
	// public function streamDownload(
	// 	callable $callback,
	// 	string|null $name = null,
	// 	array $headers = [],
	// 	string|null $disposition = 'attachment'
	// ): StreamedResponse;

	/**
	 * Create a new response for a given view.
	 */
	public function view(string|array $view, array $data = [], int $status = 200, array $headers = []): Response;
}
