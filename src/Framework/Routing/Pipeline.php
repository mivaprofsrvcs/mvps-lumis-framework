<?php

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Http\Responsable;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Pipeline\Pipeline as BasePipeline;
use Throwable;

class Pipeline extends BasePipeline
{
	/**
	 * Handles the value returned from each pipe before passing it to the next.
	 */
	protected function handleCarry(mixed $carry): mixed
	{
		return $carry instanceof Responsable
			? $carry->toResponse($this->getContainer()->make(Request::class))
			: $carry;
	}

	/**
	 * Handle the given exception.
	 *
	 * @throws \Throwable
	 */
	protected function handleException(mixed $passable, Throwable $e): mixed
	{
		if (! $this->container->bound(ExceptionHandler::class) || ! $passable instanceof Request) {
			throw $e;
		}

		$handler = $this->container->make(ExceptionHandler::class);

		$handler->report($e);

		$response = $handler->render($passable, $e);

		if (is_object($response) && method_exists($response, 'withException')) {
			$response->withException($e);
		}

		return $this->handleCarry($response);
	}
}
