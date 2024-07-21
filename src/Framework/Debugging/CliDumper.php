<?php

namespace MVPS\Lumis\Framework\Debugging;

use MVPS\Lumis\Framework\Debugging\Traits\ResolvesDumpSource;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

class CliDumper extends BaseCliDumper
{
	use ResolvesDumpSource;

	/**
	 * The base path of the application.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * The compiled view path for the application.
	 *
	 * @var string
	 */
	protected string $compiledViewPath;

	/**
	 * If the dumper is currently dumping.
	 *
	 * @var bool
	 */
	protected bool $dumping = false;

	/**
	 * The output instance.
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected OutputInterface $output;

	/**
	 * Create a new cli dumper instance.
	 */
	public function __construct(OutputInterface $output, string $basePath, string $compiledViewPath)
	{
		parent::__construct();

		$this->basePath = $basePath;
		$this->output = $output;
		$this->compiledViewPath = $compiledViewPath;

		$this->setColors($this->supportsColors());
	}

	/**
	 * Dump a variable with its source file / line.
	 */
	public function dumpWithSource(Data $data): void
	{
		if ($this->dumping) {
			$this->dump($data);

			return;
		}

		$this->dumping = true;

		$output = (string) $this->dump($data, true);
		$lines = explode("\n", $output);

		$lines[array_key_last($lines) - 1] .= $this->getDumpSourceContent();

		$this->output->write(implode("\n", $lines));

		$this->dumping = false;
	}

	/**
	 * Get the dump's source console content.
	 *
	 * @return string
	 */
	protected function getDumpSourceContent(): string
	{
		if (is_null($dumpSource = $this->resolveDumpSource())) {
			return '';
		}

		[$file, $relativeFile, $line] = $dumpSource;

		$href = $this->resolveSourceHref($file, $line);

		return sprintf(
			' <fg=gray>// <fg=gray%s>%s%s</></>',
			is_null($href) ? '' : ";href=$href",
			$relativeFile,
			is_null($line) ? '' : ":$line"
		);
	}

	/**
	 * Register the cli dumper as the default application dumper.
	 */
	public static function register(string $basePath, string $compiledViewPath): void
	{
		$cloner = new VarCloner();

		$cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

		$dumper = new static(new ConsoleOutput(), $basePath, $compiledViewPath);

		VarDumper::setHandler(fn ($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
	}

	/**
	 * {@inheritDoc}
	 */
	protected function supportsColors(): bool
	{
		return $this->output->isDecorated();
	}
}
