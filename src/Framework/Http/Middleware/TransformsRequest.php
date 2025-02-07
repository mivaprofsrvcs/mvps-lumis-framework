<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

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
		$this->cleanParameterBag($request->queryBag);

		if ($request->isJson()) {
			$this->cleanParameterBag($request->json());
		} elseif ($request->requestBag !== $request->queryBag) {
			$this->cleanParameterBag($request->requestBag);
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
