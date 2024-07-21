<?php

namespace MVPS\Lumis\Framework\View\Traits;

use InvalidArgumentException;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Support\Str;

trait ManagesLayouts
{
	/**
	 * The parent placeholder for the request.
	 *
	 * @var mixed
	 */
	protected static $parentPlaceholder = [];

	/**
	 * The parent placeholder salt for the request.
	 *
	 * @var string
	 */
	protected static string $parentPlaceholderSalt = '';

	/**
	 * All of the finished, captured sections.
	 *
	 * @var array
	 */
	protected array $sections = [];

	/**
	 * The stack of in-progress sections.
	 *
	 * @var array
	 */
	protected array $sectionStack = [];

	/**
	 * Stop injecting content into a section and append it.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function appendSection(): string
	{
		if (empty($this->sectionStack)) {
			throw new InvalidArgumentException('Cannot end a section without first starting one.');
		}

		$last = array_pop($this->sectionStack);

		if (isset($this->sections[$last])) {
			$this->sections[$last] .= ob_get_clean();
		} else {
			$this->sections[$last] = ob_get_clean();
		}

		return $last;
	}

	/**
	 * Append content to a given section.
	 */
	protected function extendSection(string $section, string $content): void
	{
		if (isset($this->sections[$section])) {
			$content = str_replace(static::parentPlaceholder($section), $content, $this->sections[$section]);
		}

		$this->sections[$section] = $content;
	}

	/**
	 * Flush all of the sections.
	 */
	public function flushSections(): void
	{
		$this->sections = [];
		$this->sectionStack = [];
	}

	/**
	 * Get the contents of a section.
	 */
	public function getSection(string $name, string|null $default = null): mixed
	{
		return $this->getSections()[$name] ?? $default;
	}

	/**
	 * Get the entire array of sections.
	 */
	public function getSections(): array
	{
		return $this->sections;
	}

	/**
	 * Check if section exists.
	 */
	public function hasSection(string $name): bool
	{
		return array_key_exists($name, $this->sections);
	}

	/**
	 * Inject inline content into a section.
	 */
	public function inject(string $section, string $content): void
	{
		$this->startSection($section, $content);
	}

	/**
	 * Get the parent placeholder for the current request.
	 */
	public static function parentPlaceholder(string $section = ''): string
	{
		if (! isset(static::$parentPlaceholder[$section])) {
			$salt = static::parentPlaceholderSalt();

			static::$parentPlaceholder[$section] = '##parent-placeholder-' . hash('xxh128', $salt . $section) . '##';
		}

		return static::$parentPlaceholder[$section];
	}

	/**
	 * Get the parent placeholder salt.
	 */
	protected static function parentPlaceholderSalt(): string
	{
		if (! static::$parentPlaceholderSalt) {
			return static::$parentPlaceholderSalt = Str::random(40);
		}

		return static::$parentPlaceholderSalt;
	}

	/**
	 * Check if section does not exist.
	 */
	public function sectionMissing(string $name): bool
	{
		return ! $this->hasSection($name);
	}

	/**
	 * Start injecting content into a section.
	 */
	public function startSection(string $section, string|null $content = null): void
	{
		if ($content === null) {
			if (ob_start()) {
				$this->sectionStack[] = $section;
			}
		} else {
			$this->extendSection($section, $content instanceof View ? $content : e($content));
		}
	}

	/**
	 * Stop injecting content into a section.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function stopSection(bool $overwrite = false): string
	{
		if (empty($this->sectionStack)) {
			throw new InvalidArgumentException('Cannot end a section without first starting one.');
		}

		$last = array_pop($this->sectionStack);

		if ($overwrite) {
			$this->sections[$last] = ob_get_clean();
		} else {
			$this->extendSection($last, ob_get_clean());
		}

		return $last;
	}

	/**
	 * Get the string contents of a section.
	 */
	public function yieldContent(string $section, string $default = ''): string
	{
		$sectionContent = $default instanceof View ? $default : e($default);

		if (isset($this->sections[$section])) {
			$sectionContent = $this->sections[$section];
		}

		$sectionContent = str_replace('@@parent', '--parent--holder--', $sectionContent);

		return str_replace(
			'--parent--holder--',
			'@parent',
			str_replace(static::parentPlaceholder($section), '', $sectionContent)
		);
	}

	/**
	 * Stop injecting content into a section and return its contents.
	 */
	public function yieldSection(): string
	{
		if (empty($this->sectionStack)) {
			return '';
		}

		return $this->yieldContent($this->stopSection());
	}
}
