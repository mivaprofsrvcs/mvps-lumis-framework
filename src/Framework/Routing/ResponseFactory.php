<?php

namespace MVPS\Lumis\Framework\Routing;

use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Http\JsonResponse;
use MVPS\Lumis\Framework\Http\Response;

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
