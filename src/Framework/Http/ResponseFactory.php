<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Http\Traits\InteractsWithContent;
use pdeans\Http\Factories\ResponseFactory as BaseResponseFactory;
use pdeans\Http\Factories\StreamFactory;

class ResponseFactory extends BaseResponseFactory
{
	use InteractsWithContent;

	/**
	 * Create a new response instance.
	 */
	public function make(mixed $content = '', $status = 200, array $headers = []): Response
	{
		$defaultContentType = 'text/html';

		if ($this->shouldBeJson($content)) {
			$content = $this->transformToJson($content);

			$defaultContentType = 'application/json';
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
