<?php

namespace MVPS\Lumis\Framework\Http\Traits;

trait InteractsWithHeaders
{
	/**
	 * Inject the provided Content-Type, if none is already present.
	 *
	 * @return array Headers with injected Content-Type
	 */
	public function injectContentType(string $contentType, array $headers): array
	{
		$hasContentType = array_reduce(
			array_keys($headers),
			static fn($carry, $item) => $carry ?: strtolower($item) === 'content-type',
			false
		);

		if (! $hasContentType) {
			$headers['content-type'] = [$contentType];
		}

		return $headers;
	}
}
