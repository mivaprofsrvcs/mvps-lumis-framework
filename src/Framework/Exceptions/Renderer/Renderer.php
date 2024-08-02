<?php

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Contracts\View\Factory;
use MVPS\Lumis\Framework\Exceptions\Renderer\Mappers\BladeMapper;
use MVPS\Lumis\Framework\Http\Request;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Throwable;

class Renderer
{
	/**
	 * The path to the renderer's distribution files.
	 *
	 * @var string
	 */
	protected const DIST = Application::FRAMEWORK_RESOURCES_PATH  . '/exceptions/renderer/dist/';

	/**
	 * The application's base path.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * The Blade mapper instance.
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Renderer\Mappers\BladeMapper
	 */
	protected BladeMapper $bladeMapper;

	/**
	 * The HTML error renderer instance.
	 *
	 * @var \Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer
	 */
	protected HtmlErrorRenderer $htmlErrorRenderer;

	/**
	 * The exception listener instance.
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Renderer\Listener
	 */
	protected Listener $listener;

	/**
	 * The view factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\View\Factory
	 */
	protected Factory $viewFactory;

	/**
	 * Create a new exception renderer instance.
	 */
	public function __construct(
		Factory $viewFactory,
		Listener $listener,
		HtmlErrorRenderer $htmlErrorRenderer,
		BladeMapper $bladeMapper,
		string $basePath
	) {
		$this->viewFactory = $viewFactory;
		$this->listener = $listener;
		$this->htmlErrorRenderer = $htmlErrorRenderer;
		$this->bladeMapper = $bladeMapper;
		$this->basePath = $basePath;
	}

	/**
	 * Get the renderer's CSS content.
	 */
	public static function css(): string
	{
		return collection([
				['styles.css', []],
				['light-mode.css', ['data-theme' => 'light']],
				['dark-mode.css', ['data-theme' => 'dark']],
			])
			->map(function ($fileAndAttributes) {
				[$filename, $attributes] = $fileAndAttributes;

				return '<style ' . collection($attributes)
					->map(function ($value, $attribute) {
						return $attribute . '="' . $value . '"';
					})
					->implode(' ')
					. '>' . file_get_contents(static::DIST . $filename) . '</style>';
			})
			->implode('');
	}

	/**
	 * Get the renderer's JavaScript content.
	 */
	public static function js(): string
	{
		return
			'<script type="text/javascript">' .
				file_get_contents(static::DIST . 'scripts.js') .
			'</script>';
	}

	/**
	 * Render the given exception as an HTML string.
	 */
	public function render(Request $request, Throwable $throwable): string
	{
		$flattenException = $this->bladeMapper->map(
			$this->htmlErrorRenderer->render($throwable),
		);

		return $this->viewFactory->make(
			'lumis-exceptions-renderer::show',
			['exception' => new Exception($flattenException, $request, $this->listener, $this->basePath)]
		)
		->render();
	}
}
