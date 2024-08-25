<?php

namespace MVPS\Lumis\Framework\Http;

use Carbon\CarbonInterval;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Bootstrap\BootProviders;
use MVPS\Lumis\Framework\Bootstrap\HandleExceptions;
use MVPS\Lumis\Framework\Bootstrap\LoadConfiguration;
use MVPS\Lumis\Framework\Bootstrap\LoadEnvironmentVariables;
use MVPS\Lumis\Framework\Bootstrap\RegisterProviders;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Contracts\Http\Kernel as KernelContract;
use MVPS\Lumis\Framework\Events\Terminating;
use MVPS\Lumis\Framework\Http\Events\RequestHandled;
use MVPS\Lumis\Framework\Http\Middleware\HandlePrecognitiveRequests;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\Middleware\SubstituteBindings;
use MVPS\Lumis\Framework\Routing\Pipeline;
use MVPS\Lumis\Framework\Routing\Router;
use Throwable;

class Kernel implements KernelContract
{
	use InteractsWithTime;

	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array<string>
	 */
	protected array $bootstrappers = [
		LoadEnvironmentVariables::class,
		LoadConfiguration::class,
		HandleExceptions::class,
		RegisterProviders::class,
		BootProviders::class,
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array<int, class-string|string>
	 */
	protected array $middleware = [];

	/**
	 * The application's middleware aliases.
	 *
	 * @var array<string, class-string|string>
	 */
	protected array $middlewareAliases = [];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array<string, array<int, class-string|string>>
	 */
	protected array $middlewareGroups = [];

	/**
	 * The priority-sorted list of middleware.
	 *
	 * Forces non-global middleware to always be in the given order.
	 *
	 * @var array<string>
	 */
	protected array $middlewarePriority = [
		HandlePrecognitiveRequests::class,
		// EncryptCookies::class,
		// AddQueuedCookiesToResponse::class,
		// StartSession::class,
		// ShareErrorsFromSession::class,
		// AuthenticatesRequests::class,
		// ThrottleRequests::class,
		// AuthenticatesSessions::class,
		SubstituteBindings::class,
		// Authorize::class,
	];

	/**
	 * All of the registered request duration handlers.
	 *
	 * @var array
	 */
	protected array $requestLifecycleDurationHandlers = [];

	/**
	 * When the kernel starting handling the current request.
	 *
	 * @var \Illuminate\Support\Carbon|null
	 */
	protected Carbon|null $requestStartedAt = null;

	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * Create a new HTTP kernel instance.
	 */
	public function __construct(Application $app, Router $router)
	{
		$this->app = $app;
		$this->router = $router;

		$this->syncMiddlewareToRouter();
	}

	/**
	 * Append the given middleware to the given middleware group.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function appendMiddlewareToGroup(string $group, string $middleware): static
	{
		if (! isset($this->middlewareGroups[$group])) {
			throw new InvalidArgumentException("The [{$group}] middleware group has not been defined.");
		}

		if (array_search($middleware, $this->middlewareGroups[$group]) === false) {
			$this->middlewareGroups[$group][] = $middleware;
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Append the given middleware to the middleware priority list.
	 */
	public function appendToMiddlewarePriority(string $middleware): static
	{
		if (! in_array($middleware, $this->middlewarePriority)) {
			$this->middlewarePriority[] = $middleware;
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Bootstrap the application.
	 */
	public function bootstrap(): void
	{
		if ($this->app->hasBeenBootstrapped()) {
			return;
		}

		$this->app->bootstrapWith($this->bootstrappers());
	}

	/**
	 * Get the bootstrap classes for the application.
	 */
	protected function bootstrappers(): array
	{
		return $this->bootstrappers;
	}

	/**
	 * Get the route dispatcher callback.
	 */
	protected function dispatchToRouter(): Closure
	{
		return function ($request) {
			$this->app->instance('request', $request);

			return $this->router->dispatch($request);
		};
	}

	/**
	 * Gather the route middleware for the given request.
	 */
	protected function gatherRouteMiddleware(Request $request): array
	{
		$route = $request->route();

		if ($route) {
			return $this->router->gatherRouteMiddleware($route);
		}

		return [];
	}

	/**
	 * Get the Lumis application instance.
	 */
	public function getApplication(): Application
	{
		return $this->app;
	}

	/**
	 * Get the application's global middleware.
	 */
	public function getGlobalMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * Get the application's route middleware aliases.
	 */
	public function getMiddlewareAliases(): array
	{
		return $this->middlewareAliases;
	}

	/**
	 * Get the application's route middleware groups.
	 */
	public function getMiddlewareGroups(): array
	{
		return $this->middlewareGroups;
	}

	/**
	 * Get the priority-sorted list of middleware.
	 */
	public function getMiddlewarePriority(): array
	{
		return $this->middlewarePriority;
	}

	/**
	 * Handle an incoming HTTP request.
	 */
	public function handle(Request $request): Response
	{
		$this->requestStartedAt = Carbon::now();

		try {
			// TODO: Enable HTTP method parameter override (_method)

			$response = $this->sendRequestThroughRouter($request);
		} catch (Throwable $e) {
			$this->reportException($e);

			$response = $this->renderException($request, $e);
		}

		$this->app['events']->dispatch(new RequestHandled($request, $response));

		return $response;
	}

	/**
	 * Determine if the kernel has a given middleware.
	 */
	public function hasMiddleware(string $middleware): bool
	{
		return in_array($middleware, $this->middleware);
	}

	/**
	 * Parse a middleware string to get the name and parameters.
	 */
	protected function parseMiddleware(string $middleware): array
	{
		[$name, $parameters] = array_pad(explode(':', $middleware, 2), 2, []);

		if (is_string($parameters)) {
			$parameters = explode(',', $parameters);
		}

		return [$name, $parameters];
	}

	/**
	 * Add a new middleware to the beginning of the stack if it does not already exist.
	 */
	public function prependMiddleware(string $middleware): static
	{
		if (array_search($middleware, $this->middleware) === false) {
			array_unshift($this->middleware, $middleware);
		}

		return $this;
	}

	/**
	 * Prepend the given middleware to the given middleware group.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function prependMiddlewareToGroup(string $group, string $middleware): static
	{
		if (! isset($this->middlewareGroups[$group])) {
			throw new InvalidArgumentException("The [{$group}] middleware group has not been defined.");
		}

		if (array_search($middleware, $this->middlewareGroups[$group]) === false) {
			array_unshift($this->middlewareGroups[$group], $middleware);
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Prepend the given middleware to the middleware priority list.
	 */
	public function prependToMiddlewarePriority(string $middleware): static
	{
		if (! in_array($middleware, $this->middlewarePriority)) {
			array_unshift($this->middlewarePriority, $middleware);
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Add a new middleware to end of the stack if it does not already exist.
	 */
	public function pushMiddleware(string $middleware): static
	{
		if (array_search($middleware, $this->middleware) === false) {
			$this->middleware[] = $middleware;
		}

		return $this;
	}

	/**
	 * Render the exception to a response.
	 */
	protected function renderException(Request $request, Throwable $e): Response
	{
		return $this->app[ExceptionHandler::class]->render($request, $e);
	}

	/**
	 * Report the exception to the exception handler.
	 */
	protected function reportException(Throwable $e): void
	{
		$this->app[ExceptionHandler::class]->report($e);
	}

	/**
	 * When the request being handled started.
	 */
	public function requestStartedAt(): Carbon|null
	{
		return $this->requestStartedAt;
	}

	/**
	 * Send the given request through the router.
	 */
	protected function sendRequestThroughRouter(Request $request): Response
	{
		$this->app->instance('request', $request);

		$this->bootstrap();

		return (new Pipeline($this->app))
			->send($request)
			->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
			->then($this->dispatchToRouter());
	}

	/**
	 * Set the Lumis application instance.
	 */
	public function setApplication(Application $app)
	{
		$this->app = $app;

		return $this;
	}

	/**
	 * Set the application's global middleware.
	 */
	public function setGlobalMiddleware(array $middleware): static
	{
		$this->middleware = $middleware;

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Set the application's route middleware aliases.
	 */
	public function setMiddlewareAliases(array $aliases): static
	{
		$this->middlewareAliases = $aliases;

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Set the application's middleware groups.
	 */
	public function setMiddlewareGroups(array $groups): static
	{
		$this->middlewareGroups = $groups;

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Set the application's middleware priority.
	 */
	public function setMiddlewarePriority(array $priority): static
	{
		$this->middlewarePriority = $priority;

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Sync the current state of the middleware to the router.
	 */
	protected function syncMiddlewareToRouter(): void
	{
		$this->router->middlewarePriority = $this->middlewarePriority;

		foreach ($this->middlewareGroups as $key => $middleware) {
			$this->router->middlewareGroup($key, $middleware);
		}

		foreach ($this->middlewareAliases as $key => $middleware) {
			$this->router->aliasMiddleware($key, $middleware);
		}
	}

	/**
	 * Call the terminate method on any terminable middleware.
	 */
	public function terminate(Request $request, Response $response): void
	{
		$this->app['events']->dispatch(new Terminating);

		$this->terminateMiddleware($request, $response);

		$this->app->terminate();

		if (is_null($this->requestStartedAt)) {
			return;
		}

		$this->requestStartedAt->setTimezone($this->app['config']->get('app.timezone') ?? 'UTC');

		foreach ($this->requestLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
			$end ??= Carbon::now();

			if ($this->requestStartedAt->diffInMilliseconds($end) > $threshold) {
				$handler($this->requestStartedAt, $request, $response);
			}
		}

		$this->requestStartedAt = null;
	}

	/**
	 * Call the terminate method on any terminable middleware.
	 */
	protected function terminateMiddleware(Request $request, Response $response): void
	{
		$middlewares = $this->app->shouldSkipMiddleware()
			? []
			: array_merge($this->gatherRouteMiddleware($request), $this->middleware);

		foreach ($middlewares as $middleware) {
			if (! is_string($middleware)) {
				continue;
			}

			[$name] = $this->parseMiddleware($middleware);

			$instance = $this->app->make($name);

			if (method_exists($instance, 'terminate')) {
				$instance->terminate($request, $response);
			}
		}
	}

	/**
	 * Register a callback to be invoked when the requests lifecycle duration exceeds a given amount of time.
	 */
	public function whenRequestLifecycleIsLongerThan(
		DateTimeInterface|CarbonInterval|float|int $threshold,
		callable $handler
	): void {
		$threshold = $threshold instanceof DateTimeInterface
			? $this->secondsUntil($threshold) * 1000
			: $threshold;

		$threshold = $threshold instanceof CarbonInterval
			? $threshold->totalMilliseconds
			: $threshold;

		$this->requestLifecycleDurationHandlers[] = [
			'threshold' => $threshold,
			'handler' => $handler,
		];
	}
}
