<?php

namespace MVPS\Lumis\Framework\View\Compilers;

use Illuminate\View\Compilers\ComponentTagCompiler as IlluminateComponentTagCompiler;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\View\Factory as FactoryContract;
use MVPS\Lumis\Framework\Filesystem\Filesystem;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\View\AnonymousComponent;
use MVPS\Lumis\Framework\View\DynamicComponent;

class ComponentTagCompiler extends IlluminateComponentTagCompiler
{
	/**
	 * The Blade compiler instance.
	 *
	 * @var \MVPS\Lumis\Framework\View\Compilers\BladeCompiler
	 */
	protected $blade;

	/**
	 * Create a new component tag compiler.
	 */
	public function __construct(array $aliases = [], array $namespaces = [], BladeCompiler|null $blade = null)
	{
		$this->aliases = $aliases;
		$this->namespaces = $namespaces;
		$this->blade = $blade ?: new BladeCompiler(new Filesystem, sys_get_temp_dir());
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function attributesToString(array $attributes, $escapeBound = true): string
	{
		return collection($attributes)
			->map(function (string $value, string $attribute) use ($escapeBound) {
				return
					$escapeBound &&
					isset($this->boundAttributes[$attribute]) &&
					$value !== 'true' &&
					! is_numeric($value)
						? "'{$attribute}' => app('blade.compiler')->sanitizeComponentAttribute({$value})"
						: "'{$attribute}' => {$value}";
			})
			->implode(',');
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function componentString(string $component, array $attributes): string
	{
		$class = $this->componentClass($component);

		[$data, $attributes] = $this->partitionDataAndAttributes($class, $attributes);

		$data = $data->mapWithKeys(function ($value, $key) {
			return [Str::camel($key) => $value];
		});

		// If the component does not exist as a class, we will treat it as a
		// class-less component. In this case, we pass the component as a view
		// parameter to the data, allowing it to be accessed within the component.
		// This enables us to render the view accordingly.
		if (! class_exists($class)) {
			$view = Str::startsWith($component, 'mail::')
				? "\$__env->getContainer()->make(MVPS\\Lumis\\Framework\\View\\Factory::class)->make('{$component}')"
				: "'$class'";

			$parameters = [
				'view' => $view,
				'data' => '[' . $this->attributesToString($data->all(), $escapeBound = false) . ']',
			];

			$class = AnonymousComponent::class;
		} else {
			$parameters = $data->all();
		}

		return "##BEGIN-COMPONENT-CLASS##@component('{$class}', '{$component}', [" . $this->attributesToString($parameters, $escapeBound = false) . '])
<?php if (isset($attributes) && $attributes instanceof MVPS\Lumis\Framework\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\\' . $class . '::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([' . $this->attributesToString($attributes->all(), $escapeAttributes = $class !== DynamicComponent::class) . ']); ?>';
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function componentClass(string $component): string
	{
		$viewFactory = Container::getInstance()->make(FactoryContract::class);

		if (isset($this->aliases[$component])) {
			if (class_exists($alias = $this->aliases[$component])) {
				return $alias;
			}

			if ($viewFactory->exists($alias)) {
				return $alias;
			}

			throw new InvalidArgumentException(
				"Unable to locate class or view [{$alias}] for component [{$component}]."
			);
		}

		$class = $this->findClassByComponent($component);

		if ($class) {
			return $class;
		}

		$class = $this->guessClassName($component);

		if (class_exists($class)) {
			return $class;
		}

		if (
			! is_null($guess = $this->guessAnonymousComponentUsingNamespaces($viewFactory, $component)) ||
			! is_null($guess = $this->guessAnonymousComponentUsingPaths($viewFactory, $component))
		) {
			return $guess;
		}

		if (Str::startsWith($component, 'mail::')) {
			return $component;
		}

		throw new InvalidArgumentException("Unable to locate a class or view for component [{$component}].");
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function guessClassName(string $component): string
	{
		$namespace = Container::getInstance()
			->make(Application::class)
			->getNamespace();

		$class = $this->formatClassName($component);

		return $namespace . 'View\\Components\\' . $class;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function parseComponentTagClassStatements(string $attributeString): string
	{
		return preg_replace_callback(
			'/@(class)(\( ( (?>[^()]+) | (?2) )* \))/x',
			function ($match) {
				if ($match[1] === 'class') {
					$match[2] = str_replace('"', "'", $match[2]);

					return ":class=\"\MVPS\Lumis\Framework\Support\Arr::toCssClasses{$match[2]}\"";
				}

				return $match[0];
			},
			$attributeString
		);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function parseComponentTagStyleStatements(string $attributeString)
	{
		return preg_replace_callback(
			'/@(style)(\( ( (?>[^()]+) | (?2) )* \))/x',
			function ($match) {
				if ($match[1] === 'style') {
					$match[2] = str_replace('"', "'", $match[2]);

					return ":style=\"\MVPS\Lumis\Framework\Support\Arr::toCssStyles{$match[2]}\"";
				}

				return $match[0];
			},
			$attributeString
		);
	}
}
