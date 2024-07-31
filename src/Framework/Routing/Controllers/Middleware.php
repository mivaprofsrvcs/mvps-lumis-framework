<?php

namespace MVPS\Lumis\Framework\Routing\Controllers;

use Closure;
use MVPS\Lumis\Framework\Support\Arr;

class Middleware
{
	/**
	 * The list of controller methods the middleware should not apply to.
	 *
	 * @var array|null
	 */
	public array|null $except;

	/**
	 * The middleware to be applied.
	 *
	 * @var Closure|string|array
	 */
	public Closure|string|array $middleware;

	/**
	 * The list of controller methods the middleware should apply to.
	 *
	 * @var array|null
	 */
	public array|null $only;

	/**
	 * Create a new controller middleware definition.
	 */
	public function __construct(Closure|string|array $middleware, array|null $only = null, array|null $except = null)
	{
		$this->middleware = $middleware;
		$this->only = $only;
		$this->except = $except;
	}

	/**
	 * Specify the controller methods the middleware should not apply to.
	 */
	public function except(array|string $except): static
	{
		$this->except = Arr::wrap($except);

		return $this;
	}

	/**
	 * Specify the only controller methods the middleware should apply to.
	 */
	public function only(array|string $only): static
	{
		$this->only = Arr::wrap($only);

		return $this;
	}
}
