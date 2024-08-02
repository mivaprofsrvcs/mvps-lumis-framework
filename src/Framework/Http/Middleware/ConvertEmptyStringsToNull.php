<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Http\Request;

class ConvertEmptyStringsToNull extends TransformsRequest
{
	/**
	 * All of the registered skip callbacks.
	 *
	 * @var array
	 */
	protected static array $skipCallbacks = [];

	/**
	 * Flush the middleware's global state.
	 */
	public static function flushState(): void
	{
		static::$skipCallbacks = [];
	}

	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		foreach (static::$skipCallbacks as $callback) {
			if ($callback($request)) {
				return $next($request);
			}
		}

		return parent::handle($request, $next);
	}

	/**
	 * Register a callback that instructs the middleware to be skipped.
	 */
	public static function skipWhen(Closure $callback): void
	{
		static::$skipCallbacks[] = $callback;
	}

	/**
	 * Transform the given value.
	 */
	protected function transform(string $key, mixed $value): mixed
	{
		return $value === '' ? null : $value;
	}
}
