<?php

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use MVPS\Lumis\Framework\Debugging\Traits\ResolvesDumpSource;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Frame
{
	use ResolvesDumpSource;

	/**
	 * The application's base path.
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * The application's class map.
	 *
	 * @var array
	 */
	protected array $classMap;

	/**
	 * The "flattened" exception instance.
	 *
	 * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
	 */
	protected FlattenException $exception;

	/**
	 * The frame's raw data from the "flattened" exception.
	 *
	 * @var array
	 */
	protected array $frame;

	/**
	 * Create a new frame instance.
	 */
	public function __construct(FlattenException $exception, array $classMap, array $frame, string $basePath)
	{
		$this->exception = $exception;
		$this->classMap = $classMap;
		$this->frame = $frame;
		$this->basePath = rtrim($basePath, '/');
	}

	/**
	 * Get the frame's function or method.
	 */
	public function callable(): string
	{
		return match (true) {
			! empty($this->frame['function']) => $this->frame['function'],
			default => 'throw',
		};
	}

	/**
	 * Get the frame's class, if any.
	 */
	public function class(): string|null
	{
		$class = array_search((string) realpath($this->frame['file']), $this->classMap, true);

		return $class === false ? null : $class;
	}

	/**
	 * Get the frame's editor link.
	 */
	public function editorHref(): string
	{
		return $this->resolveSourceHref($this->frame['file'], $this->line());
	}

	/**
	 * Get the frame's file.
	 */
	public function file(): string
	{
		return str_replace($this->basePath . '/', '', $this->frame['file']);
	}

	/**
	 * Determine if the frame is from the vendor directory.
	 */
	public function isFromVendor(): bool
	{
		return ! str_starts_with($this->frame['file'], $this->basePath) ||
			str_starts_with($this->frame['file'], $this->basePath . '/vendor');
	}

	/**
	 * Get the frame's line number.
	 */
	public function line(): int
	{
		$maxLines = count(file($this->frame['file']) ?: []);

		return $this->frame['line'] > $maxLines ? 1 : $this->frame['line'];
	}

	/**
	 * Get the frame's code snippet.
	 */
	public function snippet(): string
	{
		$contents = file($this->frame['file']) ?: [];

		$start = max($this->line() - 6, 0);

		$length = 8 * 2 + 1;

		return implode('', array_slice($contents, $start, $length));
	}

	/**
	 * Get the frame's source / origin.
	 */
	public function source(): string
	{
		return match (true) {
			is_string($this->class()) => $this->class(),
			default => $this->file(),
		};
	}
}
