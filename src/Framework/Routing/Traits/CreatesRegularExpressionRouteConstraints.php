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

namespace MVPS\Lumis\Framework\Routing\Traits;

use BackedEnum;
use MVPS\Lumis\Framework\Support\Arr;

trait CreatesRegularExpressionRouteConstraints
{
	/**
	 * Apply the given regular expression to the given parameters.
	 */
	protected function assignExpressionToParameters(array|string $parameters, string $expression): static
	{
		return $this->where(collection(Arr::wrap($parameters))
			->mapWithKeys(fn ($parameter) => [$parameter => $expression])
			->all());
	}

	/**
	 * Specify that the given route parameters must be alphabetic.
	 */
	public function whereAlpha(array|string $parameters): static
	{
		return $this->assignExpressionToParameters($parameters, '[a-zA-Z]+');
	}

	/**
	 * Specify that the given route parameters must be alphanumeric.
	 */
	public function whereAlphaNumeric(array|string $parameters): static
	{
		return $this->assignExpressionToParameters($parameters, '[a-zA-Z0-9]+');
	}

	/**
	 * Specify that the given route parameters must be one of the given values.
	 */
	public function whereIn(array|string $parameters, array $values): static
	{
		return $this->assignExpressionToParameters(
			$parameters,
			collection($values)
				->map(fn ($value) => $value instanceof BackedEnum ? $value->value : $value)
				->implode('|')
		);
	}

	/**
	 * Specify that the given route parameters must be numeric.
	 */
	public function whereNumber(array|string $parameters): static
	{
		return $this->assignExpressionToParameters($parameters, '[0-9]+');
	}

	/**
	 * Specify that the given route parameters must be ULIDs.
	 */
	public function whereUlid(array|string $parameters): static
	{
		return $this->assignExpressionToParameters(
			$parameters,
			'[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}'
		);
	}

	/**
	 * Specify that the given route parameters must be UUIDs.
	 */
	public function whereUuid(array|string $parameters): static
	{
		return $this->assignExpressionToParameters(
			$parameters,
			'[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}'
		);
	}
}
