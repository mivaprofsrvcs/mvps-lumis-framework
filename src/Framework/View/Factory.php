<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\Factory as IlluminateFactory;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\View\Factory as FactoryContract;
use MVPS\Lumis\Framework\Contracts\View\View as ViewContract;
use MVPS\Lumis\Framework\Contracts\View\ViewFinder;
use MVPS\Lumis\Framework\View\Engines\EngineResolver;

class Factory extends IlluminateFactory implements FactoryContract
{
	/**
	 * The IoC container instance.
	 *
	 * @var \MVPS\Lumis\Framework\Container\Container
	 */
	protected $container;

	/**
	 * The engine implementation.
	 *
	 * @var \MVPS\Lumis\Framework\View\Engines\EngineResolver
	 */
	protected $engines;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The view finder implementation.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\ViewFinder
	 */
	protected $finder;

	/**
	 * Create a new view factory instance.
	 */
	public function __construct(EngineResolver $engines, ViewFinder $finder, Dispatcher $events)
	{
		parent::__construct($engines, $finder, $events);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function normalizeName($name): string
	{
		return $this->normalizedNameCache[$name] ??= ViewName::normalize($name);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function viewInstance($view, $path, $data): ViewContract
	{
		return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
	}
}
