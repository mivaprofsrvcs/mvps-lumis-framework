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

use MVPS\Lumis\Framework\Support\Str;

class ResourceRegistrar
{
	/**
	 * The global parameter mapping.
	 *
	 * @var array
	 */
	protected static array $parameterMap = [];

	/**
	 * The parameters set for this resource instance.
	 *
	 * @var array|string
	 */
	protected array|string $parameters = [];

	/**
	 * The default actions for a resourceful controller.
	 *
	 * @var array<string>
	 */
	protected array $resourceDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * The default actions for a singleton resource controller.
	 *
	 * @var array<string>
	 */
	protected array $singletonResourceDefaults = ['show', 'edit', 'update'];

	/**
	 * Singular global parameters.
	 *
	 * @var bool
	 */
	protected static bool $singularParameters = true;

	/**
	 * The verbs used in the resource URIs.
	 *
	 * @var array
	 */
	protected static array $verbs = [
		'create' => 'create',
		'edit' => 'edit',
	];

	/**
	 * Create a new resource registrar instance.
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * Add the create method for a resourceful route.
	 */
	protected function addResourceCreate(string $name, string $base, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name) . '/' . static::$verbs['create'];

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'create', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the destroy method for a resourceful route.
	 */
	protected function addResourceDestroy(string $name, string $base, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name) . '/{' . $base . '}';

		$action = $this->getResourceAction($name, $controller, 'destroy', $options);

		return $this->router->delete($uri, $action);
	}

	/**
	 * Add the edit method for a resourceful route.
	 */
	protected function addResourceEdit(string $name, string $base, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name) . '/{' . $base . '}/' . static::$verbs['edit'];

		$action = $this->getResourceAction($name, $controller, 'edit', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the index method for a resourceful route.
	 */
	protected function addResourceIndex(string $name, string $base, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name);

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'index', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the show method for a resourceful route.
	 */
	protected function addResourceShow(string $name, string $base, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name) . '/{' . $base . '}';

		$action = $this->getResourceAction($name, $controller, 'show', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the store method for a resourceful route.
	 */
	protected function addResourceStore(string $name, string $base, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name);

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'store', $options);

		return $this->router->post($uri, $action);
	}

	/**
	 * Add the update method for a resourceful route.
	 */
	protected function addResourceUpdate(string $name, string $base, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name) . '/{' . $base . '}';

		$action = $this->getResourceAction($name, $controller, 'update', $options);

		return $this->router->match(['PUT', 'PATCH'], $uri, $action);
	}

	/**
	 * Add the create method for a singleton route.
	 */
	protected function addSingletonCreate(string $name, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name) . '/' . static::$verbs['create'];

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'create', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the destroy method for a singleton route.
	 */
	protected function addSingletonDestroy(string $name, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name);

		$action = $this->getResourceAction($name, $controller, 'destroy', $options);

		return $this->router->delete($uri, $action);
	}

	/**
	 * Add the edit method for a singleton route.
	 */
	protected function addSingletonEdit(string $name, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name) . '/' . static::$verbs['edit'];

		$action = $this->getResourceAction($name, $controller, 'edit', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the show method for a singleton route.
	 */
	protected function addSingletonShow(string $name, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name);

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'show', $options);

		return $this->router->get($uri, $action);
	}

	/**
	 * Add the store method for a singleton route.
	 */
	protected function addSingletonStore(string $name, string $controller, array $options): Route
	{
		$uri = $this->getResourceUri($name);

		unset($options['missing']);

		$action = $this->getResourceAction($name, $controller, 'store', $options);

		return $this->router->post($uri, $action);
	}

	/**
	 * Add the update method for a singleton route.
	 */
	protected function addSingletonUpdate(string $name, string $controller, array $options): Route
	{
		$name = $this->getShallowName($name, $options);

		$uri = $this->getResourceUri($name);

		$action = $this->getResourceAction($name, $controller, 'update', $options);

		return $this->router->match(['PUT', 'PATCH'], $uri, $action);
	}

	/**
	 * Get the URI for a nested resource segment array.
	 */
	protected function getNestedResourceUri(array $segments): string
	{
		// Iterate through segments to create placeholders for each resource and
		// its segments, resulting in a complete URI string with all nested resources.
		return implode('/', array_map(function ($segment) {
			return $segment . '/{' . $this->getResourceWildcard($segment) . '}';
		}, $segments));
	}

	/**
	 * Get the global parameter map.
	 */
	public static function getParameters(): array
	{
		return static::$parameterMap;
	}

	/**
	 * Get the action array for a resource route.
	 */
	protected function getResourceAction(string $resource, string $controller, string $method, array $options): array
	{
		$name = $this->getResourceRouteName($resource, $method, $options);

		$action = [
			'as' => $name,
			'uses' => $controller . '@' . $method,
		];

		if (isset($options['middleware'])) {
			$action['middleware'] = $options['middleware'];
		}

		if (isset($options['excluded_middleware'])) {
			$action['excluded_middleware'] = $options['excluded_middleware'];
		}

		if (isset($options['wheres'])) {
			$action['where'] = $options['wheres'];
		}

		if (isset($options['missing'])) {
			$action['missing'] = $options['missing'];
		}

		return $action;
	}

	/**
	 * Get the applicable resource methods.
	 */
	protected function getResourceMethods(array $defaults, array $options): array
	{
		$methods = $defaults;

		if (isset($options['only'])) {
			$methods = array_intersect($methods, (array) $options['only']);
		}

		if (isset($options['except'])) {
			$methods = array_diff($methods, (array) $options['except']);
		}

		return array_values($methods);
	}

	/**
	 * Extract the resource and prefix from a resource name.
	 */
	protected function getResourcePrefix(string $name): array
	{
		$segments = explode('/', $name);

		// To generate the prefix, we will concatenate all the name segments
		// using a slash ('/'). This creates a valid URI prefix. The last
		// segment will be used as the resource name.
		$prefix = implode('/', array_slice($segments, 0, -1));

		return [end($segments), $prefix];
	}

	/**
	 * Get the name for a given resource.
	 */
	protected function getResourceRouteName(string $resource, string $method, array $options): string
	{
		$name = $resource;

		// If the names array is provided, we will first check for an entry in this array.
		// Additionally, we will verify the presence of a specific method within the array,
		// allowing names to be specified at a more granular level using methods.
		if (isset($options['names'])) {
			if (is_string($options['names'])) {
				$name = $options['names'];
			} elseif (isset($options['names'][$method])) {
				return $options['names'][$method];
			}
		}

		// If a global prefix has been assigned to all names for this resource,
		// we will retrieve it. This prefix will be prepended to the name when we
		// create the name for the resource action. If no global prefix is assigned,
		// we'll use an empty string instead.
		$prefix = isset($options['as']) ? $options['as'] . '.' : '';

		return trim(sprintf('%s%s.%s', $prefix, $name, $method), '.');
	}

	/**
	 * Get the base resource URI for a given resource.
	 */
	public function getResourceUri(string $resource): string
	{
		if (! str_contains($resource, '.')) {
			return $resource;
		}

		// After constructing the base URI, we'll eliminate the parameter placeholder
		// for this base resource name. This allows individual route adders to append
		// paths as needed, since some routes do not require any parameters.
		$segments = explode('.', $resource);

		$uri = $this->getNestedResourceUri($segments);

		return str_replace('/{' . $this->getResourceWildcard(end($segments)) . '}', '', $uri);
	}

	/**
	 * Format a resource parameter for usage.
	 */
	public function getResourceWildcard(string $value): string
	{
		if (isset($this->parameters[$value])) {
			$value = $this->parameters[$value];
		} elseif (isset(static::$parameterMap[$value])) {
			$value = static::$parameterMap[$value];
		} elseif ($this->parameters === 'singular' || static::$singularParameters) {
			$value = Str::singular($value);
		}

		return str_replace('-', '_', $value);
	}

	/**
	 * Get the name for a given resource with shallowness applied when applicable.
	 */
	protected function getShallowName(string $name, array $options): string
	{
		return isset($options['shallow']) && $options['shallow']
			? last(explode('.', $name))
			: $name;
	}

	/**
	 * Build a set of prefixed resource routes.
	 */
	protected function prefixedResource(string $name, string $controller, array $options): Router
	{
		[$name, $prefix] = $this->getResourcePrefix($name);

		// Extract the base resource from the resource name. While the framework
		// supports nested resources, it is essential to identify the base resource
		// name to correctly set the placeholder for route parameters.
		$callback = function ($me) use ($name, $controller, $options) {
			$me->resource($name, $controller, $options);
		};

		return $this->router->group(compact('prefix'), $callback);
	}

	/**
	 * Build a set of prefixed singleton routes.
	 */
	protected function prefixedSingleton(string $name, string $controller, array $options): Router
	{
		[$name, $prefix] = $this->getResourcePrefix($name);

		// Extract the base resource from the resource name. While the framework
		// supports nested resources, it's necessary to identify the base resource
		// name to use as a placeholder in the route parameters.
		$callback = function ($me) use ($name, $controller, $options) {
			$me->singleton($name, $controller, $options);
		};

		return $this->router->group(compact('prefix'), $callback);
	}

	/**
	 * Route a resource to a controller.
	 */
	public function register(string $name, string $controller, array $options = []): RouteCollection|null
	{
		if (isset($options['parameters']) && ! isset($this->parameters)) {
			$this->parameters = $options['parameters'];
		}

		// Automatically prefix resource routes if the resource name contains a slash.
		// This setup eliminates the need for manual prefix configuration by the developer.
		// If no slash is present, proceed without any changes.
		if (str_contains($name, '/')) {
			$this->prefixedResource($name, $controller, $options);

			return null;
		}

		// Extract the base resource from the resource name. While the framework
		// supports nested resources, we need the base resource name to use as a
		// placeholder in route parameters.
		$base = $this->getResourceWildcard(last(explode('.', $name)));

		$defaults = $this->resourceDefaults;

		$collection = new RouteCollection;

		$resourceMethods = $this->getResourceMethods($defaults, $options);

		foreach ($resourceMethods as $method) {
			$route = $this->{'addResource' . ucfirst($method)}($name, $base, $controller, $options);

			if (isset($options['bindingFields'])) {
				$this->setResourceBindingFields($route, $options['bindingFields']);
			}

			$collection->add($route);
		}

		return $collection;
	}

	/**
	 * Set the route's binding fields if the resource is scoped.
	 */
	protected function setResourceBindingFields(Route $route, array $bindingFields): void
	{
		preg_match_all('/(?<={).*?(?=})/', $route->uri, $matches);

		$fields = array_fill_keys($matches[0], null);

		$route->setBindingFields(array_replace($fields, array_intersect_key($bindingFields, $fields)));
	}

	/**
	 * Set the global parameter mapping.
	 */
	public static function setParameters(array $parameters = []): void
	{
		static::$parameterMap = $parameters;
	}

	/**
	 * Route a singleton resource to a controller.
	 */
	public function singleton(string $name, string $controller, array $options = []): RouteCollection|null
	{
		if (isset($options['parameters']) && ! isset($this->parameters)) {
			$this->parameters = $options['parameters'];
		}

		// If the resource name contains a slash, automatically register the
		// singleton routes with a prefix. This saves the developer from needing
		// to manually configure the prefix.
		if (str_contains($name, '/')) {
			$this->prefixedSingleton($name, $controller, $options);

			return null;
		}

		$defaults = $this->singletonResourceDefaults;

		if (isset($options['creatable'])) {
			$defaults = array_merge($defaults, ['create', 'store', 'destroy']);
		} elseif (isset($options['destroyable'])) {
			$defaults = array_merge($defaults, ['destroy']);
		}

		$collection = new RouteCollection;

		$resourceMethods = $this->getResourceMethods($defaults, $options);

		foreach ($resourceMethods as $method) {
			$route = $this->{'addSingleton' . ucfirst($method)}($name, $controller, $options);

			if (isset($options['bindingFields'])) {
				$this->setResourceBindingFields($route, $options['bindingFields']);
			}

			$collection->add($route);
		}

		return $collection;
	}

	/**
	 * Set or unset the unmapped global parameters to singular.
	 */
	public static function singularParameters(bool $singular = true): void
	{
		static::$singularParameters = (bool) $singular;
	}

	/**
	 * Get or set the action verbs used in the resource URIs.
	 */
	public static function verbs(array $verbs = []): array
	{
		if (empty($verbs)) {
			return static::$verbs;
		}

		static::$verbs = array_merge(static::$verbs, $verbs);
	}
}
