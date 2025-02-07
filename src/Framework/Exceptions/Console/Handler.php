<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Exceptions\Console;

use Closure;
use MVPS\Lumis\Framework\Contracts\Exceptions\Renderer\RenderableOnEditor;
use MVPS\Lumis\Framework\Contracts\Exceptions\Renderer\RenderlessEditor;
use MVPS\Lumis\Framework\Contracts\Exceptions\Renderer\RenderlessTrace;
use MVPS\Lumis\Framework\Contracts\Exceptions\Solutions\SolutionsRepository;
use MVPS\Lumis\Framework\Exceptions\Console\Highlighter;
use MVPS\Lumis\Framework\Exceptions\Solutions\NullSolutionsRepository;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Whoops\Exception\Frame;
use Whoops\Exception\Inspector;
use Whoops\Handler\Handler as AbstractHandler;

class Handler extends AbstractHandler
{
	/**
	 *
	 * @var int
	 */
	public const VERBOSITY_NORMAL_FRAMES = 1;

	/**
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Console\ArgumentFormatter
	 */
	private ArgumentFormatter $argumentFormatter;

	/**
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Console\Highlighter
	 */
	protected Highlighter $highlighter;

	/**
	 *
	 * @var array<int, string|Closure>
	 */
	protected array $ignore = [];

	/**
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected OutputInterface $output;

	/**
	 *
	 * @var bool
	 */
	protected bool $showEditor = true;

	/**
	 *
	 * @var bool
	 */
	protected bool $showTitle = true;

	/**
	 *
	 * @var bool
	 */
	protected bool $showTrace = true;

	/**
	 * @var \MVPS\Lumis\Framework\Contracts\Exceptions\Solutions\SolutionsRepository
	 */
	protected SolutionsRepository $solutionsRepository;

	/**
	 * Create a new console handler instance.
	 */
	public function __construct(
		SolutionsRepository|null $solutionsRepository = null,
		OutputInterface|null $output = null,
		ArgumentFormatter|null $argumentFormatter = null,
		Highlighter|null $highlighter = null
	) {
		$this->solutionsRepository = $solutionsRepository ?: new NullSolutionsRepository;
		$this->output = $output ?: new ConsoleOutput;
		$this->argumentFormatter = $argumentFormatter ?: new ArgumentFormatter;
		$this->highlighter = $highlighter ?: new Highlighter;
	}

	protected function getFrames(Inspector $inspector): array
	{
		return $inspector->getFrames()
			->filter(function ($frame) {
				// Always display the full stack trace when in verbose mode.
				if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
					return true;
				}

				foreach ($this->ignore as $ignore) {
					if (is_string($ignore)) {
						// Normalize paths to Linux-style format for consistency.
						$sanitizedPath = (string) str_replace('\\', '/', $frame->getFile());

						if (preg_match($ignore, $sanitizedPath)) {
							return false;
						}
					}

					if ($ignore instanceof Closure) {
						if ($ignore($frame)) {
							return false;
						}
					}
				}

				return true;
			})
			->getArray();
	}

	protected function getFileRelativePath(string $filePath): string
	{
		$cwd = (string) getcwd();

		if (! empty($cwd)) {
			return str_replace($cwd . DIRECTORY_SEPARATOR, '', $filePath);
		}

		return $filePath;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(): int
	{
		$this->write($this->getInspector());

		return self::QUIT;
	}

	protected function render(string $message, bool $break = true): static
	{
		if ($break) {
			$this->output->writeln('');
		}

		$this->output->writeln(' ' . $message);

		return $this;
	}

	protected function renderEditor(Frame $frame): static
	{
		if (Str::upper($frame->getFile()) !== 'UNKNOWN') {
			$file = $this->getFileRelativePath((string) $frame->getFile());

			// Ensure line number is an integer. If getLine() returns null,
			// cast to int to get 0.
			$line = (int) $frame->getLine();

			$this->render('at <fg=green>' . $file . '</>:<fg=green>' . $line . '</>');

			$content = $this->highlighter->highlight(
				(string) $frame->getFileContents(),
				(int) $frame->getLine()
			);

			$this->output->writeln($content);
		}

		return $this;
	}

	protected function renderSolution(Inspector $inspector): static
	{
		$throwable = $inspector->getException();

		$solutions = $throwable instanceof Throwable
			? $this->solutionsRepository->getFromThrowable($throwable)
			: [];

		foreach ($solutions as $solution) {
			$title = $solution->getSolutionTitle();
			$description = $solution->getSolutionDescription();
			$links = $solution->getDocumentationLinks();

			$description = trim((string) preg_replace("/\n/", "\n    ", $description));

			$this->render(sprintf(
				'<fg=cyan;options=bold>i</>   <fg=default;options=bold>%s</>: %s %s',
				rtrim($title, '.'),
				$description,
				implode(
					', ',
					array_map(fn ($link) => sprintf("\n      <fg=gray>%s</>", (string) $link), $links)
				)
			));
		}

		return $this;
	}

	protected function renderTitleAndDescription(Inspector $inspector): static
	{
		$exception = $inspector->getException();

		$message = rtrim($exception->getMessage());

		if ($this->showTitle) {
			$this->render('<bg=red;options=bold> ' . $inspector->getExceptionName() . ' </>');
			$this->output->writeln('');
		}

		$this->output->writeln('<fg=default;options=bold>  ' . $message . '</>');

		return $this;
	}

	protected function renderTrace(array $frames): static
	{
		$vendorFrames = 0;
		$userFrames = 0;

		if (! empty($frames)) {
			$this->output->writeln(['']);
		}

		foreach ($frames as $i => $frame) {
			if (
				$this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE &&
				strpos($frame->getFile(), '/vendor/') !== false
			) {
				$vendorFrames++;

				continue;
			}

			if (
				$userFrames > self::VERBOSITY_NORMAL_FRAMES &&
				$this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE
			) {
				break;
			}

			$userFrames++;

			$file = $this->getFileRelativePath($frame->getFile());

			$line = $frame->getLine();

			$class = empty($frame->getClass()) ? '' : $frame->getClass() . '::';

			$function = $frame->getFunction();

			$args = $this->argumentFormatter->format($frame->getArgs());

			$pos = str_pad((string) ((int) $i + 1), 4, ' ');

			if ($vendorFrames > 0) {
				$this->output->writeln(
					sprintf("      \e[2m+%s vendor frames \e[22m", $vendorFrames)
				);

				$vendorFrames = 0;
			}

			$this->render(
				"<fg=yellow>$pos</><fg=default;options=bold>$file</>:<fg=default;options=bold>$line</>",
				(bool) $class && $i > 0
			);

			if ($class) {
				$this->render("<fg=gray>    $class$function($args)</>", false);
			}
		}

		if (! empty($frames)) {
			$this->output->writeln(['']);
		}

		return $this;
	}

	public function showEditor(bool $show): static
	{
		$this->showEditor = $show;

		return $this;
	}

	public function showTitle(bool $show): static
	{
		$this->showTitle = $show;

		return $this;
	}

	public function showTrace(bool $show): static
	{
		$this->showTrace = $show;

		return $this;
	}

	protected function write(Inspector $inspector)
	{
		$this->renderTitleAndDescription($inspector);

		$frames = $this->getFrames($inspector);

		$exception = $inspector->getException();

		$editorFrame = $exception instanceof RenderableOnEditor
			? $exception->toEditor()
			: array_shift($frames);

		if ($this->showEditor && ! is_null($editorFrame) && ! $exception instanceof RenderlessEditor) {
			$this->renderEditor($editorFrame);
		}

		$this->renderSolution($inspector);

		if ($this->showTrace && ! empty($frames) && ! $exception instanceof RenderlessTrace) {
			$this->renderTrace($frames);
		} elseif (! $exception instanceof RenderlessEditor) {
			$this->output->writeln('');
		}
	}
}
