<?php

namespace MVPS\Lumis\Framework\View;

use Closure;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\View\Compilers\BladeCompiler;
use MVPS\Lumis\Framework\View\Compilers\ComponentTagCompiler;

class DynamicComponent extends Component
{
	/**
	 * The component tag compiler instance.
	 *
	 * @var \MVPS\Lumis\Framework\View\Compilers\BladeCompiler|\MVPS\Lumis\Framework\View\Compilers\ComponentTagCompiler|null
	 */
	protected static BladeCompiler|ComponentTagCompiler|null $compiler = null;

	/**
	 * The name of the component.
	 *
	 * @var string
	 */
	public string $component;

	/**
	 * The cached component classes.
	 *
	 * @var array
	 */
	protected static array $componentClasses = [];

	/**
	 * Create a new dynamic component instance.
	 */
	public function __construct(string $component)
	{
		$this->component = $component;
	}

	/**
	 * Get the names of the variables that should be bound to the component.
	 */
	protected function bindings(string $class): array
	{
		[$data, $attributes] = $this->compiler()
			->partitionDataAndAttributes($class, $this->attributes->getAttributes());

		return array_keys($data->all());
	}

	/**
	 * Get the class for the current component.
	 */
	protected function classForComponent(): string
	{
		if (isset(static::$componentClasses[$this->component])) {
			return static::$componentClasses[$this->component];
		}

		return static::$componentClasses[$this->component] = $this->compiler()->componentClass($this->component);
	}

	/**
	 * Compile the bindings for the component.
	 */
	protected function compileBindings(array $bindings): string
	{
		return collection($bindings)
			->map(function ($key) {
				return ':' . $key . '="$' . Str::camel(str_replace([':', '.'], ' ', $key)) . '"';
			})
			->implode(' ');
	}

	/**
	 * Compile the @props directive for the component.
	 */
	protected function compileProps(array $bindings): string
	{
		if (empty($bindings)) {
			return '';
		}

		return '@props(' . '[\'' .
			implode(
				'\',\'',
				collection($bindings)
					->map(function ($dataKey) {
						return Str::camel($dataKey);
					})
					->all()
			)
		. '\']' . ')';
	}

	/**
	 * Get an instance of the Blade tag compiler.
	 */
	protected function compiler(): ComponentTagCompiler
	{
		if (is_null(static::$compiler)) {
			static::$compiler = new ComponentTagCompiler(
				Container::getInstance()->make('blade.compiler')->getClassComponentAliases(),
				Container::getInstance()->make('blade.compiler')->getClassComponentNamespaces(),
				Container::getInstance()->make('blade.compiler')
			);
		}

		return static::$compiler;
	}

	/**
	 * Compile the slots for the component.
	 */
	protected function compileSlots(array $slots): string
	{
		return collection($slots)
			->map(function ($slot, $name) {
				return $name === '__default'
					? null
					: sprintf('<x-slot name="%s" %s>{{ $%s }}</x-slot>', $name, (string) $slot->attributes, $name);
			})
			->filter()
			->implode(PHP_EOL);
	}

	/**
	 * Get the view / contents that represent the component.
	 */
	public function render(): Closure|View|string
	{
		$template = <<<'EOF'
<?php extract(collection($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [MVPS\Lumis\Framework\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
{{ props }}
<x-{{ component }} {{ bindings }} {{ attributes }}>
{{ slots }}
{{ defaultSlot }}
</x-{{ component }}>
EOF;

		return function ($data) use ($template) {
			$bindings = $this->bindings($class = $this->classForComponent());

			return str_replace(
				[
					'{{ component }}',
					'{{ props }}',
					'{{ bindings }}',
					'{{ attributes }}',
					'{{ slots }}',
					'{{ defaultSlot }}',
				],
				[
					$this->component,
					$this->compileProps($bindings),
					$this->compileBindings($bindings),
					class_exists($class) ? '{{ $attributes }}' : '',
					$this->compileSlots($data['__lumis_slots']),
					'{{ $slot ?? "" }}',
				],
				$template
			);
		};
	}
}
