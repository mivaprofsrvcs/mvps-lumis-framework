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
