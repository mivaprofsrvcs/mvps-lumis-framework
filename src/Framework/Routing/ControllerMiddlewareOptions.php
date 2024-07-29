<?php

namespace MVPS\Lumis\Framework\Routing;

class ControllerMiddlewareOptions
{
	/**
	 * The middleware options.
	 */
	protected array $options;

	/**
	 * Create a new middleware option instance.
	 */
	public function __construct(array &$options)
	{
		$this->options = &$options;
	}

	/**
	 * Set the controller methods the middleware should exclude.
	 */
	public function except(mixed $methods): static
	{
		$this->options['except'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}

	/**
	 * Set the controller methods the middleware should apply to.
	 */
	public function only(mixed $methods): static
	{
		$this->options['only'] = is_array($methods) ? $methods : func_get_args();

		return $this;
	}
}
