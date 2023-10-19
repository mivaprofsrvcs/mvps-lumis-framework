<?php

namespace MVPS\Lumis\Framework\Routing;

use Psr\Http\Message\ServerRequestInterface;

class Router
{
	/**
	 * The request currently being dispatched.
	 *
	 * @var \Psr\Http\Message\ServerRequestInterface
	 */
	protected ServerRequestInterface $currentRequest;

	/**
	 * The list of routes.
	 *
	 * @var array
	 */
	protected array $routes;

	/**
	 * Create a new Router instance.
	 */
	public function __construct()
	{
		$this->routes = [];
	}

	public function addRoute(string $method, string $uri, array|callable|null $action): Route
	{
		$route = $this->createRoute($method, $uri, $action);

		$this->routes[$method][$uri] = $route;

		return $route;
	}

	protected function createRoute(string $method, string $uri, array|callable|null $action): Route
	{
		// [
		// 	'action' => '',
		// 	'controller' => '',
		// 	'uri' => $uri,
		// 	'method' => $method,
		// ]
	}

	public function delete(string $uri, array|callable|null $action): void
	{
		// $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Dispatch the request to the application.
	 */
	public function dispatch(ServerRequestInterface $request): void
	{
		dp($request, 'req int');
		exit;
	}

	public function dispatchToRoute(ServerRequestInterface $request)
	{
		return $this->runRoute($request, $this->findRoute($request));
	}

	protected function findRoute(ServerRequestInterface $request)
	{

	}

	protected function runRoute(ServerRequestInterface $request, $route)
	{

	}

	public function get()
	{
	}

	/**
	 * Get the request currently being dispatched.
	 */
	public function getCurrentRequest(): ServerRequestInterface
	{
		return $this->currentRequest;
	}

	public function getRoutes()
	{
		return $this->routes;
	}

	protected function loadRoutes()
	{
	}

	public function patch()
	{
	}

	public function post()
	{
	}

	public function put()
	{
	}
}
