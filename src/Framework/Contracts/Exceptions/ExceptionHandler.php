<?php

namespace MVPS\Lumis\Framework\Contracts\Exceptions;

use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\Response;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

interface ExceptionHandler
{
	/**
	 * Render an exception into an HTTP response.
	 *
	 * @throws \Throwable
	 */
	public function render(Request $request, Throwable $e): Response;

	/**
	 * Render an exception to the console.
	 *
	 * @internal This method is not meant to be used or overwritten outside the framework.
	 */
	public function renderForConsole(OutputInterface $output, Throwable $e): void;

	/**
	 * Report or log an exception.
	 *
	 * @throws \Throwable
	 */
	public function report(Throwable $e): void;

	/**
	 * Determine if the exception should be reported.
	 */
	public function shouldReport(Throwable $e): bool;
}
