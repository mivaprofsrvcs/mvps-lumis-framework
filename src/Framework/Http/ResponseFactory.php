<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Contracts\Support\Renderable;
use MVPS\Lumis\Framework\Http\Traits\InteractsWithContent;
use MVPS\Lumis\Framework\Http\Traits\ResponseTrait;
use pdeans\Http\Factories\ResponseFactory as BaseResponseFactory;
use pdeans\Http\Factories\StreamFactory;

class ResponseFactory extends BaseResponseFactory
{
	use InteractsWithContent;
	use ResponseTrait;

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
}
