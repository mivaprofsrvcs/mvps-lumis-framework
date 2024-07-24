<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\View as IlluminateView;
use MVPS\Lumis\Framework\Contracts\Support\Htmlable;
use MVPS\Lumis\Framework\Contracts\View\Engine;
use MVPS\Lumis\Framework\Contracts\View\View as ViewContract;

class View extends IlluminateView implements Htmlable, ViewContract
{
	/**
	 * The engine implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Engine
	 */
	protected $engine;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\View\Factory
	 */
	protected $factory;

	/**
	 * Create a new view instance.
	 */
	public function __construct(Factory $factory, Engine $engine, string $view, string $path, mixed $data = [])
	{
		parent::__construct($factory, $engine, $view, $path, $data);
	}
}
