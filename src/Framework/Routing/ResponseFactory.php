<?php

namespace MVPS\Lumis\Framework\Routing;

use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Http\BinaryFileResponse;
use MVPS\Lumis\Framework\Http\JsonResponse;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Support\Str;
use SplFileInfo;

class ResponseFactory implements ResponseFactoryContract
{
	use Macroable;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Factory
	 */
	protected ViewFactory $view;

	/**
	 * Create a new response factory instance.
	 */
	public function __construct(ViewFactory $view)
	{
		$this->view = $view;
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
