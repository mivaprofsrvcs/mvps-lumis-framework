<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransformsRequest
{
	/**
	 * Clean the request's data.
	 */
	protected function clean(Request $request): void
	{
		$this->cleanParameterBag($request->query);

		if ($request->isJson()) {
			$this->cleanParameterBag($request->json());
		} elseif ($request->request !== $request->query) {
			$this->cleanParameterBag($request->request);
		}
	}

	/**
	 * Clean the data in the given array.
	 */
	protected function cleanArray(array $data, string $keyPrefix = ''): array
	{
		foreach ($data as $key => $value) {
			$data[$key] = $this->cleanValue($keyPrefix . $key, $value);
		}

		return $data;
	}

	/**
	 * Clean the data in the parameter bag.
	 */
	protected function cleanParameterBag(ParameterBag $bag): void
	{
		$bag->replace($this->cleanArray($bag->all()));
	}

	/**
	 * Clean the given value.
	 */
	protected function cleanValue(string $key, mixed $value): mixed
	{
		if (is_array($value)) {
			return $this->cleanArray($value, $key . '.');
		}

		return $this->transform($key, $value);
	}

	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		$this->clean($request);

		return $next($request);
	}

	/**
	 * Transform the given value.
	 */
	protected function transform(string $key, mixed $value): mixed
	{
		return $value;
	}
}
