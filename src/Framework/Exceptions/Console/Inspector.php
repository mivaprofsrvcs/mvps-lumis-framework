<?php

namespace MVPS\Lumis\Framework\Exceptions\Console;

use Whoops\Exception\Inspector as ExceptionInspector;

class Inspector extends ExceptionInspector
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function getTrace($e): array
	{
		return $e->getTrace();
	}
}
