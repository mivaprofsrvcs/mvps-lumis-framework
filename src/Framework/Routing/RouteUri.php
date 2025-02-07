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

namespace MVPS\Lumis\Framework\Routing;

class RouteUri
{
	/**
	 * The fields that should be used when resolving bindings.
	 *
	 * @var array
	 */
	public array $bindingFields;

	/**
	 * The route URI.
	 *
	 * @var string
	 */
	public string $uri;

	/**
	 * Create a new route URI instance.
	 */
	public function __construct(string $uri, array $bindingFields = [])
	{
		$this->uri = $uri;
		$this->bindingFields = $bindingFields;
	}

	/**
	 * Parse the given URI.
	 */
	public static function parse(string $uri): static
	{
		preg_match_all('/\{([\w\:]+?)\??\}/', $uri, $matches);

		$bindingFields = [];

		foreach ($matches[0] as $match) {
			if (! str_contains($match, ':')) {
				continue;
			}

			$segments = explode(':', trim($match, '{}?'));

			$bindingFields[$segments[0]] = $segments[1];

			$uri = str_contains($match, '?')
				? str_replace($match, '{' . $segments[0] . '?}', $uri)
				: str_replace($match, '{' . $segments[0] . '}', $uri);
		}

		return new static($uri, $bindingFields);
	}
}
