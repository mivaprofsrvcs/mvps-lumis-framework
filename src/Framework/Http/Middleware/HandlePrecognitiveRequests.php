<?php

namespace MVPS\Lumis\Framework\Http\Middleware;

use Closure;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Routing\CallableDispatcher;
use MVPS\Lumis\Framework\Contracts\Routing\ControllerDispatcher;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\PrecognitionCallableDispatcher;
use MVPS\Lumis\Framework\Routing\PrecognitionControllerDispatcher;

class HandlePrecognitiveRequests
{
	/**
	 * The container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
	 */
	protected Container $container;

	/**
	 * Create a new handle precognitive requests HTTP middleware instance.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Append the appropriate "Vary" header to the given response.
	 */
	protected function appendVaryHeader(Request $request, Response $response): Response
	{
		return tap(
			$response,
			fn () => $response->headerBag->set(
				'Vary',
				implode(', ', array_filter([$response->headerBag->get('Vary'), 'Precognition']))
			)
		);
	}

	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if (! $request->isAttemptingPrecognition()) {
			return $this->appendVaryHeader($request, $next($request));
		}

		$this->prepareForPrecognition($request);

		return tap($next($request), function ($response) use ($request) {
			$response->headerBag->set('Precognition', 'true');

			$this->appendVaryHeader($request, $response);
		});
	}

	/**
	 * Prepare to handle a precognitive request.
	 */
	protected function prepareForPrecognition(Request $request): void
	{
		$request->attributeBag->set('precognitive', true);

		$this->container->bind(CallableDispatcher::class, fn ($app) => new PrecognitionCallableDispatcher($app));
		$this->container->bind(ControllerDispatcher::class, fn ($app) => new PrecognitionControllerDispatcher($app));
	}
}
