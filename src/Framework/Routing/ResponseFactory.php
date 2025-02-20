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

namespace MVPS\Lumis\Framework\Routing;

use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Http\BinaryFileResponse;
use MVPS\Lumis\Framework\Http\JsonResponse;
use MVPS\Lumis\Framework\Http\RedirectResponse;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\StreamedResponse;
use MVPS\Lumis\Framework\Routing\Exceptions\StreamedResponseException;
use MVPS\Lumis\Framework\Support\Str;
use SplFileInfo;
use Throwable;

class ResponseFactory implements ResponseFactoryContract
{
	use Macroable;

	/**
	 * The redirector instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Redirector
	 */
	protected Redirector $redirector;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Factory
	 */
	protected ViewFactory $view;

	/**
	 * Create a new response factory instance.
	 */
	public function __construct(ViewFactory $view, Redirector $redirector)
	{
		$this->view = $view;
		$this->redirector = $redirector;
	}

	/**
	 * Create a new file download response.
	 */
	public function download(
		SplFileInfo|string $file,
		string|null $name = null,
		array $headers = [],
		string|null $disposition = 'attachment'
	): BinaryFileResponse {
		$response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

		if (! is_null($name)) {
			return $response->setContentDisposition($disposition, $name, $this->fallbackName($name));
		}

		return $response;
	}

	/**
	 * Converts a string to ASCII characters that are
	 * equivalent to the given name.
	 */
	protected function fallbackName(string $name): string
	{
		return str_replace('%', '', Str::ascii($name));
	}

	/**
	 * Return the raw contents of a binary file.
	 */
	public function file(SplFileInfo|string $file, array $headers = []): BinaryFileResponse
	{
		return new BinaryFileResponse($file, 200, $headers);
	}

	public function json(mixed $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
	{
		return new JsonResponse($data, $status, $headers, $options);
	}

	/**
	 * Create a new JSONP response instance.
	 */
	public function jsonp(
		string $callback,
		mixed $data = [],
		int $status = 200,
		array $headers = [],
		int $options = 0
	): JsonResponse {
		return $this->json($data, $status, $headers, $options)->setCallback($callback);
	}

	/**
	 * Create a new response instance.
	 */
	public function make(mixed $content = '', $status = 200, array $headers = []): Response
	{
		return new Response($content, $status, $headers);
	}

	/**
	 * Create a new "no content" response.
	 */
	public function noContent(int $status = 204, array $headers = []): Response
	{
		return $this->make('', $status, $headers);
	}

	/**
	 * Create a new redirect response, while putting the
	 * current URLin the session.
	 */
	public function redirectGuest(
		string $path,
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): RedirectResponse {
		return $this->redirector->guest($path, $status, $headers, $secure);
	}

	/**
	 * Create a new redirect response to the given path.
	 */
	public function redirectTo(
		string $path,
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): RedirectResponse {
		return $this->redirector->to($path, $status, $headers, $secure);
	}

	/**
	 * Create a new redirect response to a controller action.
	 */
	public function redirectToAction(
		array|string $action,
		mixed $parameters = [],
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->redirector->action($action, $parameters, $status, $headers);
	}

	/**
	 * Create a new redirect response to the previously intended location.
	 */
	public function redirectToIntended(
		string $default = '/',
		int $status = 302,
		array $headers = [],
		bool|null $secure = null
	): RedirectResponse {
		return $this->redirector->intended($default, $status, $headers, $secure);
	}

	/**
	 * Create a new redirect response to a named route.
	 */
	public function redirectToRoute(
		string $route,
		mixed $parameters = [],
		int $status = 302,
		array $headers = []
	): RedirectResponse {
		return $this->redirector->route($route, $parameters, $status, $headers);
	}

	/**
	 * Create a new streamed response instance.
	 */
	public function stream(callable $callback, int $status = 200, array $headers = []): StreamedResponse
	{
		return new StreamedResponse($callback, $status, $headers);
	}

	/**
	 * Create a new streamed response instance as a file download.
	 */
	public function streamDownload(
		callable $callback,
		string|null $name = null,
		array $headers = [],
		string|null $disposition = 'attachment'
	): StreamedResponse {
		$withWrappedException = function () use ($callback) {
			try {
				$callback();
			} catch (Throwable $e) {
				throw new StreamedResponseException($e);
			}
		};

		$response = new StreamedResponse($withWrappedException, 200, $headers);

		if (! is_null($name)) {
			$response->headerBag->set(
				'Content-Disposition',
				$response->headerBag->makeDisposition(
					$disposition,
					$name,
					$this->fallbackName($name)
				)
			);
		}

		return $response;
	}

	/**
	 * Create a new response for a given view.
	 */
	public function view(string|array $view, array $data = [], int $status = 200, array $headers = []): Response
	{
		if (is_array($view)) {
			return $this->make($this->view->first($view, $data), $status, $headers);
		}

		return $this->make($this->view->make($view, $data), $status, $headers);
	}
}
