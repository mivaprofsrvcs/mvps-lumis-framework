<?php

namespace MVPS\Lumis\Framework\Routing;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use MVPS\Lumis\Framework\Contracts\Routing\RouteCollection as RouteCollectionContract;
use MVPS\Lumis\Framework\Http\Exceptions\MethodNotAllowedException;
use MVPS\Lumis\Framework\Http\Exceptions\NotFoundException;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;
use Traversable;

abstract class AbstractRouteCollection implements Countable, IteratorAggregate, RouteCollectionContract
{
	/**
	 * Add a route to the SymfonyRouteCollection instance.
	 *
	 * @throws \LogicException
	 */
	protected function addToSymfonyRoutesCollection(
		SymfonyRouteCollection $symfonyRoutes,
		Route $route
	): SymfonyRouteCollection {
		$name = $route->getName();

		if (! is_null($name) && str_ends_with($name, '.') && ! is_null($symfonyRoutes->get($name))) {
			$name = null;
		}

		if (! $name) {
			$route->name($this->generateRouteName());

			$this->add($route);
		} elseif (! is_null($symfonyRoutes->get($name))) {
			throw new LogicException(sprintf(
				'Unable to prepare route [%s] for serialization. Another route has already been assigned name [%s].',
				$route->uri(),
				$name
			));
		}

		$symfonyRoutes->add($route->getName(), $route->toSymfonyRoute());

		return $symfonyRoutes;
	}

	/**
	 * Determine if any routes match on another HTTP verb.
	 */
	protected function checkForAlternateVerbs(Request $request): array
	{
		$methods = array_diff(Router::$verbs, [$request->getMethod()]);

		return array_values(array_filter(
			$methods,
			function ($method) use ($request) {
				return ! is_null($this->matchAgainstRoutes($this->get($method), $request, false));
			}
		));
	}

	/**
	 * Compile the routes for caching.
	 */
	public function compile(): array
	{
		$compiled = $this->dumper()->getCompiledRoutes();

		$attributes = [];

		foreach ($this->getRoutes() as $route) {
			$attributes[$route->getName()] = [
				'action' => $route->getAction(),
				'bindingFields' => $route->bindingFields(),
				'defaults' => $route->defaults,
				'fallback' => $route->isFallback,
				'methods' => $route->methods(),
				'uri' => $route->uri(),
				'wheres' => $route->wheres,
			];
		}

		return compact('compiled', 'attributes');
	}

	/**
	 * Count the number of items in the collection.
	 */
	public function count(): int
	{
		return count($this->getRoutes());
	}

	/**
	 * Return the CompiledUrlMatcherDumper instance for the route collection.
	 */
	public function dumper(): CompiledUrlMatcherDumper
	{
		return new CompiledUrlMatcherDumper($this->toSymfonyRouteCollection());
	}

	/**
	 * Get a randomly generated route name.
	 */
	protected function generateRouteName(): string
	{
		return 'generated::' . Str::random();
	}

	/**
	 * Get an iterator for the items.
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->getRoutes());
	}

	/**
	 * Get a route (if necessary) that responds when other available methods are present.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\MethodNotAllowedException
	 */
	protected function getRouteForMethods(Request $request, array $methods): Route
	{
		if ($request->isMethod('OPTIONS')) {
			return (new Route('OPTIONS', $request->getPath(), function () use ($methods) {
				return response('', 200, ['Allow' => implode(',', $methods)]);
			}))->bind($request);
		}

		$this->requestMethodNotAllowed($request, $methods, $request->getMethod());
	}

	/**
	 * Handle the matched route.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\NotFoundException
	 */
	protected function handleMatchedRoute(Request $request, Route|null $route): Route
	{
		if (! is_null($route)) {
			return $route->bind($request);
		}

		// If no route was found, check if a matching route exists for a different
		// HTTP verb. If such a route is found, throw a MethodNotAllowed exception
		// and inform the user agent which HTTP verb should be used for this route.
		$others = $this->checkForAlternateVerbs($request);

		if (count($others) > 0) {
			return $this->getRouteForMethods($request, $others);
		}

		throw new NotFoundException(sprintf('The route %s could not be found.', $request->getPath()));
	}

	/**
	 * Determine if a route in the array matches the request.
	 */
	protected function matchAgainstRoutes(array $routes, Request $request, bool $includingMethod = true): Route|null
	{
		[$fallbacks, $routes] = collection($routes)->partition(function ($route) {
			return $route->isFallback;
		});

		return $routes->merge($fallbacks)
			->first(
				fn (Route $route) => $route->matches($request, $includingMethod)
			);
	}

	/**
	 * Throw a method not allowed HTTP exception.
	 *
	 * @throws \MVPS\Lumis\Framework\Http\Exceptions\MethodNotAllowedException
	 */
	protected function requestMethodNotAllowed(Request $request, array $others, string $method): void
	{
		throw new MethodNotAllowedException(
			$others,
			sprintf(
				'The %s method is not supported for route %s. Supported methods: %s.',
				$method,
				$request->getPath(),
				implode(', ', $others)
			)
		);
	}

	/**
	 * Convert the collection to a Symfony RouteCollection instance.
	 */
	public function toSymfonyRouteCollection(): SymfonyRouteCollection
	{
		$symfonyRoutes = new SymfonyRouteCollection;

		$routes = $this->getRoutes();

		foreach ($routes as $route) {
			if (! $route->isFallback) {
				$symfonyRoutes = $this->addToSymfonyRoutesCollection($symfonyRoutes, $route);
			}
		}

		foreach ($routes as $route) {
			if ($route->isFallback) {
				$symfonyRoutes = $this->addToSymfonyRoutesCollection($symfonyRoutes, $route);
			}
		}

		return $symfonyRoutes;
	}
}
