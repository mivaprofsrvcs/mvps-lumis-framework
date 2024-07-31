<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory;
use MVPS\Lumis\Framework\Http\Response;

class ViewController extends Controller
{
	/**
	 * The response factory implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Routing\ResponseFactory
	 */
	protected ResponseFactory $response;

	/**
	 * Create a new view controller instance.
	 */
	public function __construct(ResponseFactory $response)
	{
		$this->response = $response;
	}

	/**
	 * Invoke the controller method.
	 */
	public function __invoke(mixed ...$args): Response
	{
		$routeParameters = array_filter(
			$args,
			fn ($key) => ! in_array($key, ['view', 'data', 'status', 'headers']),
			ARRAY_FILTER_USE_KEY
		);

		$args['data'] = array_merge($args['data'], $routeParameters);

		return $this->response->view($args['view'], $args['data'], $args['status'], $args['headers']);
	}

	/**
	 * Execute an action on the controller.
	 */
	public function callAction(string $method, array $parameters): Response
	{
		return $this->{$method}(...$parameters);
	}
}
