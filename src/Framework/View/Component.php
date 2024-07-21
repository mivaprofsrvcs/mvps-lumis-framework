<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\Component as IlluminateComponent;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\View\Factory;

abstract class Component extends IlluminateComponent
{
	/**
	 * {@inheritdoc}
	 *
	 * @var \MVPS\Lumis\Framework\View\ComponentAttributeBag
	 */
	public $attributes;

	/**
	 * {@inheritdoc}
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Factory|null
	 */
	protected static $factory;

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function createBladeViewFromString($factory, $contents): string
	{
		$directory = Container::getInstance()['config']->get('view.compiled');

		$factory->addNamespace('__components', $directory);

		$viewFile = $directory . '/' . hash('xxh128', $contents) . '.blade.php';

		if (! is_file($viewFile)) {
			if (! is_dir($directory)) {
				mkdir($directory, 0755, true);
			}

			file_put_contents($viewFile, $contents);
		}

		return '__components::' . basename($viewFile, '.blade.php');
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function createInvokableVariable(string $method): InvokableComponentVariable
	{
		return new InvokableComponentVariable(function () use ($method) {
			return $this->{$method}();
		});
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function factory(): Factory
	{
		if (is_null(static::$factory)) {
			static::$factory = Container::getInstance()->make('view');
		}

		return static::$factory;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function newAttributeBag(array $attributes = [])
	{
		return new ComponentAttributeBag($attributes);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public static function resolve($data): static
	{
		if (static::$componentsResolver) {
			return call_user_func(static::$componentsResolver, static::class, $data);
		}

		$parameters = static::extractConstructorParameters();

		$dataKeys = array_keys($data);

		if (empty(array_diff($parameters, $dataKeys))) {
			return new static(...array_intersect_key($data, array_flip($parameters)));
		}

		return Container::getInstance()->make(static::class, $data);
	}
}
