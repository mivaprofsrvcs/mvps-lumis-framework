<?php

namespace MVPS\Lumis\Framework\View\Exceptions;

use ErrorException;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Support\Reflector;

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
