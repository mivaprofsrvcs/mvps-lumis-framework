<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

use Closure;
use MVPS\Lumis\Framework\Support\Str;

trait CompilesEchos
{
	/**
	 * Custom rendering callbacks for stringable objects.
	 *
	 * @var array
	 */
	protected array $echoHandlers = [];

	/**
	 * Add an instance of the blade echo handler to the start of the compiled string.
	 */
	protected function addBladeCompilerVariable(string $result): string
	{
		return "<?php \$__bladeCompiler = app('blade.compiler'); ?>" . $result;
	}

	/**
	 * Apply the echo handler for the value if it exists.
	 */
	public function applyEchoHandler(string $value): string
	{
		if (is_object($value) && isset($this->echoHandlers[get_class($value)])) {
			return call_user_func($this->echoHandlers[get_class($value)], $value);
		}

		if (is_iterable($value) && isset($this->echoHandlers['iterable'])) {
			return call_user_func($this->echoHandlers['iterable'], $value);
		}

		return $value;
	}

	/**
	 * Compile Blade echos into valid PHP.
	 */
	public function compileEchos(string $value): string
	{
		foreach ($this->getEchoMethods() as $method) {
			$value = $this->$method($value);
		}

		return $value;
	}

	/**
	 * Compile the escaped echo statements.
	 */
	protected function compileEscapedEchos(string $value): string
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

		$callback = function ($matches) {
			$whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

			return $matches[1]
				? $matches[0]
				: "<?php echo e({$this->wrapInEchoHandler($matches[2])}); ?>{$whitespace}";
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the "raw" echo statements.
	 */
	protected function compileRawEchos(string $value): string
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

		$callback = function ($matches) {
			$whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

			return $matches[1]
				? substr($matches[0], 1)
				: "<?php echo {$this->wrapInEchoHandler($matches[2])}; ?>{$whitespace}";
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the "regular" echo statements.
	 */
	protected function compileRegularEchos(string $value): string
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

		$callback = function ($matches) {
			$whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

			$wrapped = sprintf($this->echoFormat, $this->wrapInEchoHandler($matches[2]));

			return $matches[1] ? substr($matches[0], 1) : "<?php echo {$wrapped}; ?>{$whitespace}";
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Get the echo methods in the proper order for compilation.
	 */
	protected function getEchoMethods(): array
	{
		return [
			'compileRawEchos',
			'compileEscapedEchos',
			'compileRegularEchos',
		];
	}

	/**
	 * Add a handler to be executed before echoing a given class.
	 */
	public function stringable(string|callable $class, callable|null $handler = null): void
	{
		if ($class instanceof Closure) {
			[$class, $handler] = [$this->firstClosureParameterType($class), $class];
		}

		$this->echoHandlers[$class] = $handler;
	}

	/**
	 * Wrap the echoable value in an echo handler if applicable.
	 */
	protected function wrapInEchoHandler(string $value): string
	{
		$value = Str::of($value)
			->trim()
			->when(str_ends_with($value, ';'), function ($str) {
				return $str->beforeLast(';');
			});

		return empty($this->echoHandlers)
			? $value
			: '$__bladeCompiler->applyEchoHandler(' . $value . ')';
	}
}
