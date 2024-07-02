<?php

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
