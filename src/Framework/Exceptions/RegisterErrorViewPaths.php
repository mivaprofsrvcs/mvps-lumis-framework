<?php

namespace MVPS\Lumis\Framework\Exceptions;

class RegisterErrorViewPaths
{
	/**
	 * Register the error view paths.
	 */
	public function __invoke(): void
	{
		view()->replaceNamespace(
			'errors',
			collection(config('view.paths'))
				->map(function ($path) {
					return "{$path}/errors";
				})
				->push(__DIR__ . '/views')
				->all()
		);
	}
}
