<?php

namespace MVPS\Lumis\Framework\Debugging;

use MVPS\Lumis\Framework\Debugging\Traits\ResolvesDumpSource;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as BaseHtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class HtmlDumper extends BaseHtmlDumper
{
	use ResolvesDumpSource;

	/**
	 * The location of the source on expanded dump.
	 *
	 * @var string
	 */
	protected const EXPANDED_SEPARATOR = 'class=sf-dump-expanded>';

	/**
	 * The location of the source on non-expanded dump.
	 *
	 * @var string
	 */
	protected const NON_EXPANDED_SEPARATOR = "\n</pre><script>";

	/**
	 * The base path of the application.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * Determines if the dumper is currently dumping.
	 *
	 * @var bool
	 */
	protected bool $dumping = false;

	/**
	 * The source output color.
	 *
	 * @var string
	 */
	protected static string $sourceOutputColor = '#A0A0A0';

	/**
	 * Create a new html dumper instance.
	 */
	public function __construct(string $basePath)
	{
		parent::__construct();

		$this->basePath = $basePath;
	}

	/**
	 * Dump a variable with its source file and line number.
	 */
	public function dumpWithSource(Data $data): void
	{
		if ($this->dumping) {
			$this->dump($data);

			return;
		}

		$this->dumping = true;

		$output = (string) $this->dump($data, true);

		$output = match (true) {
			str_contains($output, static::EXPANDED_SEPARATOR) => str_replace(
				static::EXPANDED_SEPARATOR,
				static::EXPANDED_SEPARATOR . $this->getDumpSourceContent(),
				$output,
			),
			str_contains($output, static::NON_EXPANDED_SEPARATOR) => str_replace(
				static::NON_EXPANDED_SEPARATOR,
				$this->getDumpSourceContent() . static::NON_EXPANDED_SEPARATOR,
				$output,
			),
			default => $output,
		};

		fwrite($this->outputStream, $output);

		$this->dumping = false;
	}

	/**
	 * Get the source html content of the dump.
	 */
	protected function getDumpSourceContent(): string
	{
		$dumpSource = $this->resolveDumpSource();

		if (is_null($dumpSource)) {
			return '';
		}

		[$file, $relativeFile, $line] = $dumpSource;

		$source = sprintf('%s%s', $relativeFile, is_null($line) ? '' : ":{$line}");

		$href = $this->resolveSourceHref($file, $line);

		if ($href) {
			$source = sprintf('<a href="%s">%s</a>', $href, $source);
		}

		return sprintf('<span style="color: %s;"> // %s</span>', static::$sourceOutputColor, $source);
	}

	/**
	 * Register the html dumper as the default application dumper.
	 */
	public static function register(string $basePath): void
	{
		$cloner = new VarCloner();

		$cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

		$dumper = new static($basePath);

		VarDumper::setHandler(fn ($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
	}
}
