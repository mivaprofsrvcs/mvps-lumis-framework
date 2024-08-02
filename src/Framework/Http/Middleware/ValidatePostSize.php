<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Http\Exceptions\ContentTooLargeException;
use MVPS\Lumis\Framework\Http\Request;

class ValidatePostSize
{
	/**
	 * Determine the server 'post_max_size' as bytes.
	 */
	protected function getPostMaxSize(): int
	{
		$postMaxSize = ini_get('post_max_size');

		if (is_numeric($postMaxSize)) {
			return (int) $postMaxSize;
		}

		$metric = strtoupper(substr($postMaxSize, -1));

		$postMaxSize = (int) $postMaxSize;

		return match ($metric) {
			'K' => $postMaxSize * 1024,
			'M' => $postMaxSize * 1048576,
			'G' => $postMaxSize * 1073741824,
			default => $postMaxSize,
		};
	}

	/**
	 * Handle an incoming request.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\ContentTooLargeException
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		$max = $this->getPostMaxSize();

		if ($max > 0 && $request->server('CONTENT_LENGTH') > $max) {
			throw new ContentTooLargeException;
		}

		return $next($request);
	}
}
