<?php

namespace MVPS\Lumis\Framework\Exceptions;

use Throwable;
use MVPS\Lumis\Framework\Contracts\Debug\ExceptionHandler;

class Handler implements ExceptionHandler
{
	public function render($request, Throwable $e)
	{
	}

	public function renderForConsole($output, Throwable $e)
	{
	}
}
