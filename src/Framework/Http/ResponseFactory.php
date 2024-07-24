<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Contracts\Support\Renderable;
use MVPS\Lumis\Framework\Contracts\View\Factory as ViewFactory;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContent;
use MVPS\Lumis\Framework\Http\Traits\ResponseTrait;
use pdeans\Http\Factories\ResponseFactory as BaseResponseFactory;
use pdeans\Http\Factories\StreamFactory;

class ResponseFactory extends BaseResponseFactory
{
	use InteractsWithContent;
	use ResponseTrait;

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
	 * Create a new response instance.
	 */
	public function make(mixed $content = '', $status = 200, array $headers = []): Response
	{
		$this->original = $content;

		$defaultContentType = 'text/html';

		if ($this->shouldBeJson($content)) {
			$content = $this->transformToJson($content);

			$defaultContentType = 'application/json';
		} elseif ($content instanceof Renderable) {
			$content = $content->render();
		} else {
			$content = (string) $content;
		}

		if (empty($headers)) {
			$headers = ['Content-Type' => $defaultContentType];
		}

		return new Response(
			(new StreamFactory)->createStream($content),
			$status,
			$headers
		);
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
