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

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Exceptions\UrlGenerationException;
use MVPS\Lumis\Framework\Support\Arr;

class RouteUrlGenerator
{
	/**
	 * The named parameter defaults.
	 *
	 * @var array
	 */
	public array $defaultParameters = [];

	/**
	 * Mapping of characters that should not be URL encoded.
	 *
	 * @var array
	 */
	public array $dontEncode = [
		'%2F' => '/',
		'%40' => '@',
		'%3A' => ':',
		'%3B' => ';',
		'%2C' => ',',
		'%3D' => '=',
		'%2B' => '+',
		'%21' => '!',
		'%2A' => '*',
		'%7C' => '|',
		'%3F' => '?',
		'%26' => '&',
		'%23' => '#',
		'%25' => '%',
	];

	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request $request;

	/**
	 * The URL generator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\UrlGenerator
	 */
	protected UrlGenerator $url;

	/**
	 * Create a new Route URL generator.
	 */
	public function __construct(UrlGenerator $url, Request $request)
	{
		$this->url = $url;
		$this->request = $request;
	}

	/**
	 * Add the port to the domain if necessary.
	 */
	protected function addPortToDomain(string $domain): string
	{
		$secure = $this->request->isSecure();

		$port = (int) $this->request->getPort();

		return ($secure && $port === 443) || (! $secure && $port === 80)
			? $domain
			: $domain . ':' . $port;
	}

	/**
	 * Add a query string to the URI.
	 */
	protected function addQueryString(string $uri, array $parameters): mixed
	{
		$fragment = parse_url($uri, PHP_URL_FRAGMENT);

		// After verifying that all URI parameters are present, we encode and prepare
		// the URI for return to the developer. If the URI is meant to be absolute,
		// we return it unchanged; otherwise, we strip out the URL's root.
		if (! is_null($fragment)) {
			$uri = preg_replace('/#.*/', '', $uri);
		}

		$uri .= $this->getRouteQueryString($parameters);

		return is_null($fragment) ? $uri : $uri . "#{$fragment}";
	}

	/**
	 * Set the default named parameters used by the URL generator.
	 */
	public function defaults(array $defaults): void
	{
		$this->defaultParameters = array_merge($this->defaultParameters, $defaults);
	}

	/**
	 * Format the domain and port for the route and request.
	 */
	protected function formatDomain(Route $route): string
	{
		return $this->addPortToDomain($this->getRouteScheme($route) . $route->getDomain());
	}

	/**
	 * Get the numeric parameters from a given list.
	 */
	protected function getNumericParameters(array $parameters): array
	{
		return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Get the formatted domain for a given route.
	 */
	protected function getRouteDomain(Route $route): string|null
	{
		return $route->getDomain() ? $this->formatDomain($route) : null;
	}

	/**
	 * Get the query string for a given route.
	 */
	protected function getRouteQueryString(array $parameters): string
	{
		if (count($parameters) === 0) {
			return '';
		}

		$query = Arr::query($keyed = $this->getStringParameters($parameters));

		if (count($keyed) < count($parameters)) {
			$query .= '&' . implode('&', $this->getNumericParameters($parameters));
		}

		$query = trim($query, '&');

		return $query === '' ? '' : "?{$query}";
	}

	/**
	 * Get the scheme for the given route.
	 */
	protected function getRouteScheme(Route $route): string
	{
		if ($route->isHttpOnly()) {
			return 'http://';
		} elseif ($route->isHttpsOnly()) {
			return 'https://';
		}

		return $this->url->formatScheme();
	}

	/**
	 * Get the string parameters from a given list.
	 */
	protected function getStringParameters(array $parameters): array
	{
		return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Replace all of the named parameters in the path.
	 */
	protected function replaceNamedParameters(string $path, array &$parameters): string
	{
		return preg_replace_callback('/\{(.*?)(\?)?\}/', function ($matches) use (&$parameters) {
			if (isset($parameters[$matches[1]]) && $parameters[$matches[1]] !== '') {
				return Arr::pull($parameters, $matches[1]);
			} elseif (isset($this->defaultParameters[$matches[1]])) {
				return $this->defaultParameters[$matches[1]];
			} elseif (isset($parameters[$matches[1]])) {
				Arr::pull($parameters, $matches[1]);
			}

			return $matches[0];
		}, $path);
	}

	/**
	 * Replace the parameters on the root path.
	 */
	protected function replaceRootParameters(Route $route, string|null $domain, array &$parameters): string
	{
		$scheme = $this->getRouteScheme($route);

		return $this->replaceRouteParameters($this->url->formatRoot($scheme, $domain), $parameters);
	}

	/**
	 * Replace all of the wildcard parameters for a route path.
	 */
	protected function replaceRouteParameters(string $path, array &$parameters): string
	{
		$path = $this->replaceNamedParameters($path, $parameters);

		$path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
			// Reset only the numeric keys
			$parameters = array_merge($parameters);

			return ! isset($parameters[0]) && ! str_ends_with($match[0], '?}')
				? $match[0]
				: Arr::pull($parameters, 0);
		}, $path);

		return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
	}

	/**
	 * Generate a URL for the given route.
	 *
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\UrlGenerationException
	 */
	public function to(Route $route, array $parameters = [], bool $absolute = false): string
	{
		$domain = $this->getRouteDomain($route);

		// First, we will construct the complete URI, including the root and query string.
		// Once the URI is constructed, we will check for any missing parameters.
		// If any required parameters are missing, we will throw an exception to
		// alert the developers that a required parameter was not provided.
		$uri = $this->addQueryString($this->url->format(
			$this->replaceRootParameters($route, $domain, $parameters),
			$this->replaceRouteParameters($route->uri(), $parameters),
			$route
		), $parameters);

		if (preg_match_all('/{(.*?)}/', $uri, $matchedMissingParameters)) {
			throw UrlGenerationException::forMissingParameters($route, $matchedMissingParameters[1]);
		}

		// After verifying that all URI parameters are present, we encode and prepare
		// the URI for return to the developer. If the URI is meant to be absolute,
		// we return it unchanged; otherwise, we strip out the URL's root.
		$uri = strtr(rawurlencode($uri), $this->dontEncode);

		if (! $absolute) {
			$uri = preg_replace('#^(//|[^/?])+#', '', $uri);
			$baseUrl = $this->request->getBaseUrl();

			if ($baseUrl) {
				$uri = preg_replace('#^' . $baseUrl . '#i', '', $uri);
			}

			return '/' . ltrim($uri, '/');
		}

		return $uri;
	}
}
