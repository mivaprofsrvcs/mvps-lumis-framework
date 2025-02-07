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

namespace MVPS\Lumis\Framework\View\Exceptions;

use ErrorException;
use Illuminate\Support\Reflector;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;

class ViewException extends ErrorException
{
	/**
	 * Render the exception into an HTTP response.
	 */
	public function render(Request $request): Response|null
	{
		$exception = $this->getPrevious();

		if ($exception && method_exists($exception, 'render')) {
			return $exception->render($request);
		}

		return null;
	}

	/**
	 * Report the exception.
	 */
	public function report(): bool|null
	{
		$exception = $this->getPrevious();

		if (Reflector::isCallable($reportCallable = [$exception, 'report'])) {
			return Container::getInstance()->call($reportCallable);
		}

		return false;
	}
}
