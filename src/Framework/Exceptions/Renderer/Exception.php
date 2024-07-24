<?php

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use Composer\Autoload\ClassLoader;
use MVPS\Lumis\Framework\Bootstrap\HandleExceptions;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Http\Request;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Exception
{
	/**
	 * The application's base path.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * The "flattened" exception instance.
	 *
	 * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
	 */
	protected FlattenException $exception;

	/**
	 * The current request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request $request;

	/**
	 * Creates a new exception renderer instance.
	 */
	public function __construct(FlattenException $exception, Request $request, string $basePath)
	{
		$this->exception = $exception;
		$this->request = $request;
		$this->basePath = $basePath;
	}

	/**
	 * Get the application's route context.
	 */
	public function applicationRouteContext(): array
	{
		$route = $this->request()->route();

		return $route ? array_filter([
			'controller' => $route->getActionName(),
			'route name' => $route->getName() ?: null,
		]) : [];
	}

	/**
	 * Get the application's route parameters context.
	 */
	public function applicationRouteParametersContext(): array|null
	{
		$parameters = $this->request()->route()?->getParameters();

		return $parameters
			? json_encode(
				array_map(fn ($value) => $value, (array) $parameters),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
			)
			: null;
	}

	/**
	 * Get the exception class name.
	 */
	public function class(): string
	{
		return $this->exception->getClass();
	}

	/**
	 * Get the first "non-vendor" frame index.
	 */
	public function defaultFrame(): int
	{
		$key = array_search(false, array_map(function (Frame $frame) {
			return $frame->isFromVendor();
		}, $this->frames()->all()));

		return $key === false ? 0 : $key;
	}

	/**
	 * Get the exception's frames.
	 */
	public function frames(): Collection
	{
		$classMap = once(fn () => array_map(function ($path) {
			return (string) realpath($path);
		}, array_values(ClassLoader::getRegisteredLoaders())[0]->getClassMap()));

		$trace = array_values(array_filter(
			$this->exception->getTrace(),
			fn ($trace) => isset($trace['file']),
		));

		if (($trace[1]['class'] ?? '') === HandleExceptions::class) {
			array_shift($trace);
			array_shift($trace);
		}

		return collection(
			array_map(
				fn (array $trace) => new Frame($this->exception, $classMap, $trace, $this->basePath),
				$trace
			)
		);
	}

	/**
	 * Get the exception message.
	 */
	public function message(): string
	{
		return $this->exception->getMessage();
	}

	/**
	 * Get the exception's request instance.
	 */
	public function request(): Request
	{
		return $this->request;
	}

	/**
	 * Get the request's body parameters.
	 */
	public function requestBody(): string|null
	{
		$payload = $this->request()->input();

		if (empty($payload)) {
			return null;
		}

		$json = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		return str_replace('\\', '', $json);
	}

	/**
	 * Get the request's headers.
	 */
	public function requestHeaders(): array
	{
		return array_map(function (array $header) {
			return implode(', ', $header);
		}, $this->request()->getHeaders());
	}

	/**
	 * Get the exception title.
	 */
	public function title(): string
	{
		return $this->exception->getStatusText();
	}
}
