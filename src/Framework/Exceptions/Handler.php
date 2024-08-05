<?php

namespace MVPS\Lumis\Framework\Exceptions;

use Closure;
use Exception;
use Illuminate\Console\View\Components\BulletList;
use Illuminate\Console\View\Components\Error;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Cache\RateLimiter;
use MVPS\Lumis\Framework\Cache\RateLimiting\Limit;
use MVPS\Lumis\Framework\Cache\RateLimiting\Unlimited;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionHandler;
use MVPS\Lumis\Framework\Contracts\Exceptions\ExceptionRenderer;
use MVPS\Lumis\Framework\Contracts\Http\HttpException as HttpExceptionContract;
use MVPS\Lumis\Framework\Contracts\Http\Responsable;
use MVPS\Lumis\Framework\Exceptions\Console\Handler as ConsoleHandler;
use MVPS\Lumis\Framework\Exceptions\Console\Inspector;
use MVPS\Lumis\Framework\Exceptions\Renderer\Renderer;
use MVPS\Lumis\Framework\Http\Exceptions\BadRequestException;
use MVPS\Lumis\Framework\Http\Exceptions\HttpException;
use MVPS\Lumis\Framework\Http\Exceptions\HttpResponseException;
use MVPS\Lumis\Framework\Http\Exceptions\NotFoundException;
use MVPS\Lumis\Framework\Http\JsonResponse;
use MVPS\Lumis\Framework\Http\RedirectResponse;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Lottery;
use MVPS\Lumis\Framework\Support\Reflector;
use MVPS\Lumis\Framework\Support\Traits\ReflectsClosures;
use MVPS\Lumis\Framework\Validation\Exceptions\ValidationException;
use pdeans\Http\Contracts\ExceptionInterface as RequestExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface as SymfonyConsoleExceptionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;
use WeakMap;

class Handler implements ExceptionHandler
{
	use ReflectsClosures;

	/**
	 * The container implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Container\Container
	 */
	protected Container $container;

	/**
	 * The callbacks that should be used to build exception context data.
	 *
	 * @var array
	 */
	protected array $contextCallbacks = [];

	/**
	 * A list of the inputs that are never flashed for validation exceptions.
	 */
	protected array $dontFlash = [
		'current_password',
		'password',
		'password_confirmation',
	];

	/**
	 * A list of the exception types that are not reported.
	 *
	 * @var array
	 */
	protected array $dontReport = [];

	/**
	 * The registered exception mappings.
	 *
	 * @var array
	 */
	protected array $exceptionMap = [];

	/**
	 * The callback that prepares responses to be returned to the browser.
	 *
	 * @var callable|null
	 */
	protected $finalizeResponseCallback;

	/**
	 * Indicates that throttled keys should be hashed.
	 *
	 * @var bool
	 */
	protected bool $hashThrottleKeys = true;

	/**
	 * A list of the internal exception types that should not be reported.
	 *
	 * @var array
	 */
	protected array $internalDontReport = [
		BackedEnumCaseNotFoundException::class,
		HttpException::class,
		HttpResponseException::class,
		ModelNotFoundException::class,
		MultipleRecordsFoundException::class,
		RecordsNotFoundException::class,
		RequestExceptionInterface::class,
		ValidationException::class,
	];

	/**
	 * A map of exceptions with their corresponding custom log levels.
	 *
	 * @var array
	 */
	protected array $levels = [];

	/**
	 * The callbacks that should be used during rendering.
	 *
	 * @var array<\Closure>
	 */
	protected array $renderCallbacks = [];

	/**
	 * The callbacks that should be used during reporting.
	 *
	 * @var array<\MVPS\Lumis\Framework\Exceptions\ReportableHandler>
	 */
	protected array $reportCallbacks = [];

	/**
	 * The already reported exception map.
	 *
	 * @var \WeakMap
	 */
	protected WeakMap $reportedExceptionMap;

	/**
	 * The callback that determines if the exception handler response should be JSON.
	 *
	 * @var callable|null
	 */
	protected $shouldRenderJsonWhenCallback;

	/**
	 * The callbacks that should be used to throttle reportable exceptions.
	 *
	 * @var array
	 */
	protected array $throttleCallbacks = [];

	/**
	 * Indicates that an exception instance should only be reported once.
	 *
	 * @var bool
	 */
	protected bool $withoutDuplicates = false;

	/**
	 * Create a new exception handler instance.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->reportedExceptionMap = new WeakMap;

		$this->register();
	}

	/**
	 * Register a closure that should be used to build exception context data.
	 */
	public function buildContextUsing(Closure $contextCallback): static
	{
		$this->contextCallbacks[] = $contextCallback;

		return $this;
	}

	/**
	 * Create the context array for logging the given exception.
	 */
	protected function buildExceptionContext(Throwable $e): array
	{
		return array_merge(
			$this->exceptionContext($e),
			$this->context(),
			['exception' => $e]
		);
	}

	/**
	 * Get the default context variables for logging.
	 */
	protected function context(): array
	{
		return [];
	}

	/**
	 * Convert the given exception to an array.
	 */
	protected function convertExceptionToArray(Throwable $e): array
	{
		return config('app.debug')
			?
				[
					'message' => $e->getMessage(),
					'exception' => get_class($e),
					'file' => $e->getFile(),
					'line' => $e->getLine(),
					'trace' => collection($e->getTrace())
						->map(fn ($trace) => Arr::except($trace, ['args']))
						->all(),
				]
			:
				[
					'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
				];
	}

	/**
	 * Create a Symfony response for the given exception.
	 */
	protected function convertExceptionToResponse(Throwable $e): Response
	{
		$statusCode = 500;
		$headers = [];

		if ($this->isHttpException($e)) {
			$statusCode = $e->getStatusCode();
			$headers = $e->getHeaders();
		}

		return new Response($this->renderExceptionContent($e), $statusCode, $headers);
	}

	/**
	 * Create a response object from the given validation exception.
	 */
	protected function convertValidationExceptionToResponse(ValidationException $e, Request $request): Response
	{
		if ($e->response) {
			return $e->response;
		}

		return $this->shouldReturnJson($request, $e)
			? $this->invalidJson($request, $e)
			: $this->invalid($request, $e);
	}

	/**
	 * Indicate that the given attributes should never be flashed to the session on validation errors.
	 */
	public function dontFlash(array|string $attributes): static
	{
		$this->dontFlash = array_values(array_unique(
			array_merge($this->dontFlash, Arr::wrap($attributes))
		));

		return $this;
	}

	/**
	 * Indicate that the given exception type should not be reported.
	 *
	 * Alias of "ignore".
	 */
	public function dontReport(array|string $exceptions): static
	{
		return $this->ignore($exceptions);
	}

	/**
	 * Do not report duplicate exceptions.
	 */
	public function dontReportDuplicates(): static
	{
		$this->withoutDuplicates = true;

		return $this;
	}

	/**
	 * Get the default exception context variables for logging.
	 */
	protected function exceptionContext(Throwable $e): array
	{
		$context = [];

		if (method_exists($e, 'context')) {
			$context = $e->context();
		}

		foreach ($this->contextCallbacks as $callback) {
			$context = array_merge($context, $callback($e, $context));
		}

		return $context;
	}

	/**
	 * Prepare the final, rendered response to be returned to the browser.
	 */
	protected function finalizeRenderedResponse(Request $request, Response $response, Throwable $e): Response
	{
		return $this->finalizeResponseCallback
			? call_user_func($this->finalizeResponseCallback, $response, $e, $request)
			: $response;
	}

	/**
	 * Get the view used to render HTTP exceptions.
	 */
	protected function getHttpExceptionView(HttpExceptionContract $e): string|null
	{
		$view = 'errors::' . $e->getStatusCode();

		if (view()->exists($view)) {
			return $view;
		}

		$view = substr($view, 0, -2) . 'xx';

		if (view()->exists($view)) {
			return $view;
		}

		return null;
	}

	/**
	 * Indicate that the given exception type should not be reported.
	 */
	public function ignore(array|string $exceptions): static
	{
		$exceptions = Arr::wrap($exceptions);

		$this->dontReport = array_values(array_unique(array_merge($this->dontReport, $exceptions)));

		return $this;
	}

	/**
	 * Convert a validation exception into a response.
	 */
	protected function invalid(Request $request, ValidationException $exception): Response|JsonResponse|RedirectResponse
	{
		return redirect($exception->redirectTo ?? url()->previous())
			->withInput(Arr::except($request->input(), $this->dontFlash))
			->withErrors($exception->errors(), $request->input('_error_bag', $exception->errorBag));
	}

	/**
	 * Convert a validation exception into a JSON response.
	 */
	protected function invalidJson(Request $request, ValidationException $exception): JsonResponse
	{
		$data = [
			'message' => $exception->getMessage(),
			'errors' => $exception->errors()
		];

		return new JsonResponse($data, $exception->status);
	}

	/**
	 * Determine if the given exception is an HTTP exception.
	 */
	protected function isHttpException(Throwable $e): bool
	{
		return $e instanceof HttpExceptionContract;
	}

	/**
	 * Set the log level for the given exception type.
	 */
	public function level(string $type, string $level): static
	{
		$this->levels[$type] = $level;

		return $this;
	}

	/**
	 * Register a new exception mapping.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function map(Closure|string $from, Closure|string|null $to = null): static
	{
		if (is_string($to)) {
			$to = fn ($exception) => new $to('', 0, $exception);
		}

		if (is_callable($from) && is_null($to)) {
			$from = $this->firstClosureParameterType($to = $from);
		}

		if (! is_string($from) || ! $to instanceof Closure) {
			throw new InvalidArgumentException('Invalid exception mapping.');
		}

		$this->exceptionMap[$from] = $to;

		return $this;
	}

	/**
	 * Map the exception using a registered mapper if possible.
	 */
	protected function mapException(Throwable $e): Throwable
	{
		if (method_exists($e, 'getInnerException') && ($inner = $e->getInnerException()) instanceof Throwable) {
			return $inner;
		}

		foreach ($this->exceptionMap as $class => $mapper) {
			if (is_a($e, $class)) {
				return $mapper($e);
			}
		}

		return $e;
	}

	/**
	 * Create a new logger instance.
	 */
	protected function newLogger(): LoggerInterface
	{
		return $this->container->make(LoggerInterface::class);
	}

	/**
	 * Prepare exception for rendering.
	 */
	protected function prepareException(Throwable $e): Throwable
	{
		return match (true) {
			$e instanceof BackedEnumCaseNotFoundException => new NotFoundException($e->getMessage(), $e),
			$e instanceof ModelNotFoundException => new NotFoundException($e->getMessage(), $e),
			$e instanceof RecordsNotFoundException => new NotFoundException('Not found.', $e),
			$e instanceof RequestExceptionInterface => new BadRequestException('Bad request.', $e),
			default => $e,
		};
	}

	/**
	 * Prepare a JSON response for the given exception.
	 */
	protected function prepareJsonResponse(Request $request, Throwable $e): JsonResponse
	{
		$statusCode = 500;
		$headers = [];

		if ($this->isHttpException($e)) {
			$statusCode = $e->getStatusCode();
			$headers = $e->getHeaders();
		}

		return new JsonResponse(
			$this->convertExceptionToArray($e),
			$statusCode,
			$headers,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * Prepare a response for the given exception.
	 */
	protected function prepareResponse(Request $request, Throwable $e): Response|JsonResponse|RedirectResponse
	{
		if (! $this->isHttpException($e)) {
			if (config('app.debug')) {
				return $this->toResponse($this->convertExceptionToResponse($e), $e)
					->prepare($request);
			}

			$e = new HttpException(500, $e->getMessage(), $e);
		}

		return $this->toResponse($this->renderHttpException($e), $e)
			->prepare($request);
	}

	/**
	 * Register the exception handling callbacks for the application.
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Register the error template hint paths.
	 */
	protected function registerErrorViewPaths(): void
	{
		(new RegisterErrorViewPaths)();
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @throws \Throwable
	 */
	public function render(Request $request, Throwable $e): Response
	{
		$e = $this->mapException($e);

		if (method_exists($e, 'render') && $response = $e->render($request)) {
			return $this->finalizeRenderedResponse(
				$request,
				$this->container->make('router')::toResponse($request, $response),
				$e
			);
		}

		if ($e instanceof Responsable) {
			return $this->finalizeRenderedResponse($request, $e->toResponse($request), $e);
		}

		$e = $this->prepareException($e);

		if ($response = $this->renderViaCallbacks($request, $e)) {
			return $this->finalizeRenderedResponse($request, $response, $e);
		}

		return $this->finalizeRenderedResponse($request, match (true) {
			$e instanceof HttpResponseException => $e->getResponse(),
			$e instanceof ValidationException => $this->convertValidationExceptionToResponse($e, $request),
			default => $this->renderExceptionResponse($request, $e),
		}, $e);
	}

	/**
	 * Register a renderable callback.
	 */
	public function renderable(callable $renderUsing): static
	{
		if (! $renderUsing instanceof Closure) {
			$renderUsing = Closure::fromCallable($renderUsing);
		}

		$this->renderCallbacks[] = $renderUsing;

		return $this;
	}

	/**
	 * Get the response content for the given exception.
	 */
	protected function renderExceptionContent(Throwable $e): string
	{
		try {
			if (config('app.debug')) {
				if (app()->has(ExceptionRenderer::class)) {
					return $this->renderExceptionWithCustomRenderer($e);
				} elseif ($this->container->bound(Renderer::class)) {
					return $this->container->make(Renderer::class)->render(request(), $e);
				}
			}

			return $this->renderExceptionWithSymfony($e, config('app.debug'));
		} catch (Throwable $e) {
			return $this->renderExceptionWithSymfony($e, config('app.debug'));
		}
	}

	/**
	 * Render a default exception response if any.
	 */
	protected function renderExceptionResponse(Request $request, Throwable $e): Response|JsonResponse|RedirectResponse
	{
		return $this->shouldReturnJson($request, $e)
			? $this->prepareJsonResponse($request, $e)
			: $this->prepareResponse($request, $e);
	}

	/**
	 * Render an exception to a string using the registered `ExceptionRenderer`.
	 */
	protected function renderExceptionWithCustomRenderer(Throwable $e): string
	{
		return app(ExceptionRenderer::class)->render($e);
	}

	/**
	 * Render an exception to a string using Symfony.
	 */
	protected function renderExceptionWithSymfony(Throwable $e, bool $debug): string
	{
		$renderer = new HtmlErrorRenderer($debug);

		return $renderer->render($e)->getAsString();
	}

	/**
	 * Render an exception to the console.
	 *
	 * @internal This method is not meant to be used or overwritten outside of
	 * the framework.
	 */
	public function renderForConsole(OutputInterface $output, Throwable $e): void
	{
		if ($e instanceof SymfonyConsoleExceptionInterface) {
			if ($e instanceof CommandNotFoundException) {
				$message = str($e->getMessage())
					->explode('.')
					->first();

				$alternatives = $e->getAlternatives();

				if (! empty($alternatives)) {
					$message .= '. Did you mean one of these?';

					with(new Error($output))->render($message);

					with(new BulletList($output))->render($alternatives);

					$output->writeln('');
				} else {
					with(new Error($output))->render($message);
				}

				return;
			}

			(new ConsoleApplication)->renderThrowable($e, $output);

			return;
		}

		$handler = new ConsoleHandler;

		$handler->setInspector(new Inspector($e));

		$handler->handle();
	}

	/**
	 * Render the given HTTP exception.
	 */
	protected function renderHttpException(HttpExceptionContract $e): Response
	{
		$this->registerErrorViewPaths();

		$view = $this->getHttpExceptionView($e);

		if ($view) {
			try {
				return response()->view(
					$view,
					['errors' => new ViewErrorBag, 'exception' => $e],
					$e->getStatusCode(),
					$e->getHeaders()
				);
			} catch (Throwable $t) {
				config('app.debug') && throw $t;

				$this->report($t);
			}
		}

		return $this->convertExceptionToResponse($e);
	}

	/**
	 * Try to render a response from request and exception via render callbacks.
	 *
	 * @throws \ReflectionException
	 */
	protected function renderViaCallbacks(Request $request, Throwable $e): mixed
	{
		foreach ($this->renderCallbacks as $renderCallback) {
			foreach ($this->firstClosureParameterTypes($renderCallback) as $type) {
				if (is_a($e, $type)) {
					$response = $renderCallback($e, $request);

					if (! is_null($response)) {
						return $response;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Report or log an exception.
	 *
	 * @throws \Throwable
	 */
	public function report(Throwable $e): void
	{
		$e = $this->mapException($e);

		if ($this->shouldntReport($e)) {
			return;
		}

		$this->reportThrowable($e);
	}

	/**
	 * Register a reportable callback.
	 */
	public function reportable(callable $reportUsing): ReportableHandler
	{
		if (! $reportUsing instanceof Closure) {
			$reportUsing = Closure::fromCallable($reportUsing);
		}

		return tap(new ReportableHandler($reportUsing), function ($callback) {
			$this->reportCallbacks[] = $callback;
		});
	}

	/**
	 * Reports error based on report method on exception or to logger.
	 *
	 * @throws \Throwable
	 */
	protected function reportThrowable(Throwable $e): void
	{
		$this->reportedExceptionMap[$e] = true;

		$reportCallable = [$e, 'report'];

		if (Reflector::isCallable($reportCallable) && $this->container->call($reportCallable) !== false) {
			return;
		}

		foreach ($this->reportCallbacks as $reportCallback) {
			if ($reportCallback->handles($e) && $reportCallback($e) === false) {
				return;
			}
		}

		try {
			$logger = $this->newLogger();
		} catch (Exception) {
			throw $e;
		}

		$level = Arr::first(
			$this->levels,
			fn ($level, $type) => $e instanceof $type,
			LogLevel::ERROR
		);

		$context = $this->buildExceptionContext($e);

		method_exists($logger, $level)
			? $logger->{$level}($e->getMessage(), $context)
			: $logger->log($level, $e->getMessage(), $context);
	}

	/**
	 * Prepare the final, rendered response for an exception using the given callback.
	 */
	public function respondUsing(callable $callback): static
	{
		$this->finalizeResponseCallback = $callback;

		return $this;
	}

	/**
	 * Determine if the exception is in the "do not report" list.
	 */
	protected function shouldntReport(Throwable $e): bool
	{
		if ($this->withoutDuplicates && ($this->reportedExceptionMap[$e] ?? false)) {
			return true;
		}

		$dontReport = array_merge($this->dontReport, $this->internalDontReport);

		if (! is_null(Arr::first($dontReport, fn ($type) => $e instanceof $type))) {
			return true;
		}

		return rescue(fn () => with($this->throttle($e), function ($throttle) use ($e) {
			if ($throttle instanceof Unlimited || $throttle === null) {
				return false;
			}

			if ($throttle instanceof Lottery) {
				return ! $throttle($e);
			}

			return ! $this->container->make(RateLimiter::class)
				->attempt(
					with(
						$throttle->key ?: 'illuminate:foundation:exceptions:' . $e::class,
						fn ($key) => $this->hashThrottleKeys ? md5($key) : $key
					),
					$throttle->maxAttempts,
					fn () => true,
					$throttle->decaySeconds
				);
		}), rescue: false, report: false);
	}

	/**
	 * Register the callable that determines if the exception handler response should be JSON.
	 */
	public function shouldRenderJsonWhen(callable $callback): static
	{
		$this->shouldRenderJsonWhenCallback = $callback;

		return $this;
	}

	/**
	 * Determine if the exception should be reported.
	 */
	public function shouldReport(Throwable $e): bool
	{
		return ! $this->shouldntReport($e);
	}

	/**
	 * Determine if the exception handler response should be JSON.
	 */
	protected function shouldReturnJson(Request $request, Throwable $e): bool
	{
		return $this->shouldRenderJsonWhenCallback
			? call_user_func($this->shouldRenderJsonWhenCallback, $request, $e)
			: $request->expectsJson();
	}

	/**
	 * Remove the given exception class from the list of exceptions that should be ignored.
	 */
	public function stopIgnoring(array|string $exceptions): static
	{
		$exceptions = Arr::wrap($exceptions);

		$this->dontReport = collection($this->dontReport)
			->reject(fn ($ignored) => in_array($ignored, $exceptions))
			->values()
			->all();

		$this->internalDontReport = collection($this->internalDontReport)
			->reject(fn ($ignored) => in_array($ignored, $exceptions))
			->values()
			->all();

		return $this;
	}

	/**
	 * Throttle the given exception.
	 */
	protected function throttle(Throwable $e): Lottery|Limit|null
	{
		foreach ($this->throttleCallbacks as $throttleCallback) {
			foreach ($this->firstClosureParameterTypes($throttleCallback) as $type) {
				if (is_a($e, $type)) {
					$response = $throttleCallback($e);

					if (! is_null($response)) {
						return $response;
					}
				}
			}
		}

		return Limit::none();
	}

	/**
	 * Specify the callback that should be used to throttle reportable exceptions.
	 */
	public function throttleUsing(callable $throttleUsing): static
	{
		if (! $throttleUsing instanceof Closure) {
			$throttleUsing = Closure::fromCallable($throttleUsing);
		}

		$this->throttleCallbacks[] = $throttleUsing;

		return $this;
	}

	/**
	 * Map the given exception into an HTTP response.
	 */
	protected function toResponse(Response $response, Throwable $e): Response
	{
		if ($response instanceof RedirectResponse) {
			$response = new RedirectResponse(
				$response->getTargetUrl(),
				$response->getStatusCode(),
				$response->headerBag->all()
			);
		} else {
			$response = new Response(
				$response->getContent(),
				$response->getStatusCode(),
				$response->headerBag->all()
			);
		}

		return $response->withException($e);
	}

	/**
	 * Convert an authentication exception into a response.
	 *
	 * TODO: Implement this with authentication
	 */
	// protected function unauthenticated(
	// 	Request $request,
	// 	AuthenticationException $exception
	// ): Response|JsonResponse|RedirectResponse {
	// 	return $this->shouldReturnJson($request, $exception)
	// 		? response()->json(['message' => $exception->getMessage()], 401)
	// 		: redirect()->guest($exception->redirectTo($request) ?? route('login'));
	// }
}
