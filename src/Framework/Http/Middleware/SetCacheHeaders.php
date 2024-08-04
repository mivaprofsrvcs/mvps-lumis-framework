<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use MVPS\Lumis\Framework\Http\BinaryFileResponse;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Http\StreamedResponse;
use MVPS\Lumis\Framework\Support\Str;

class SetCacheHeaders
{
	/**
	 * Add cache related HTTP headers.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function handle(Request $request, Closure $next, string|array $options = []): Response
	{
		$response = $next($request);

		if (
			! $request->isMethodCacheable() ||
			(
				! $response->getContent() &&
				! $response instanceof BinaryFileResponse &&
				! $response instanceof StreamedResponse
			)
		) {
			return $response;
		}

		if (is_string($options)) {
			$options = $this->parseOptions($options);
		}

		if (! $response->isSuccessful()) {
			return $response;
		}

		if (isset($options['etag']) && $options['etag'] === true) {
			$options['etag'] = $response->getEtag() ?? md5($response->getContent());
		}

		if (isset($options['last_modified'])) {
			if (is_numeric($options['last_modified'])) {
				$options['last_modified'] = Carbon::createFromTimestamp(
					$options['last_modified'],
					date_default_timezone_get()
				);
			} else {
				$options['last_modified'] = Carbon::parse($options['last_modified']);
			}
		}

		$response->setCache($options);

		if ($response->isNotModified($request)) {
			$response = $response->withNotModified();
		}

		return $response;
	}

	/**
	 * Parse the given header options.
	 */
	protected function parseOptions(string $options): array
	{
		return collection(explode(';', rtrim($options, ';')))
			->mapWithKeys(function ($option) {
				$data = explode('=', $option, 2);

				return [$data[0] => $data[1] ?? true];
			})
			->all();
	}

	/**
	 * Specify the options for the middleware.
	 */
	public static function using(array|string $options): string
	{
		if (is_string($options)) {
			return static::class . ':' . $options;
		}

		return collection($options)
			->map(function ($value, $key) {
				if (is_bool($value)) {
					return $value ? $key : null;
				}

				return is_int($key) ? $value : "{$key}={$value}";
			})
			->filter()
			->map(fn ($value) => Str::finish($value, ';'))
			->pipe(fn ($options) => rtrim(static::class . ':' . $options->implode(''), ';'));
	}
}
