<?php

namespace MVPS\Lumis\Framework\Contracts\Debug;

use Throwable;

// TODO: Flesh this out once HTTP request and Console foundation is implemented
interface ExceptionHandler
{
	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  Request  $request
	 * @param  \Throwable  $e
	 * @return \MVPS\Lumis\Framework\Http\Response
	 *
	 * @throws \Throwable
	 */
	// public function render($request, Throwable $e);

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  \Throwable  $e
	 * @return void
	 *
	 * @internal This method is not meant to be used or overwritten outside the framework.
	 */
	// public function renderForConsole($output, Throwable $e);
}
