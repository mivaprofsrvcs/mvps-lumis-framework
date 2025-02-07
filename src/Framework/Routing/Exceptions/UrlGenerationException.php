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

namespace MVPS\Lumis\Framework\Routing\Exceptions;

use Exception;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Support\Str;

class UrlGenerationException extends Exception
{
	/**
	 * Create a new exception for missing route parameters.
	 */
	public static function forMissingParameters(Route $route, array $parameters = []): static
	{
		$parameterLabel = Str::plural('parameter', count($parameters));

		$message = sprintf(
			'Missing required %s for [Route: %s] [URI: %s]',
			$parameterLabel,
			$route->getName(),
			$route->uri()
		);

		if (count($parameters) > 0) {
			$message .= sprintf(' [Missing %s: %s]', $parameterLabel, implode(', ', $parameters));
		}

		$message .= '.';

		return new static($message);
	}
}
