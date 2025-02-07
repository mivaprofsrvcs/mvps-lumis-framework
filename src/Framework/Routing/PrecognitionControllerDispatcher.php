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

use RuntimeException;

class PrecognitionControllerDispatcher extends ControllerDispatcher
{
	/**
	 * Dispatch a request to a given controller and method.
	 */
	public function dispatch(Route $route, mixed $controller, string $method): void
	{
		$this->ensureMethodExists($controller, $method);

		$this->resolveParameters($route, $controller, $method);

		abort(204, headers: ['Precognition-Success' => 'true']);
	}

	/**
	 * Ensure that the given method exists on the controller.
	 */
	protected function ensureMethodExists(object $controller, string $method): static
	{
		if (method_exists($controller, $method)) {
			return $this;
		}

		$class = $controller::class;

		throw new RuntimeException(
			"Attempting to predict the outcome of the [{$class}::{$method}()] method but the method is not defined."
		);
	}
}
