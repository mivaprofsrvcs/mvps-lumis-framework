<?php

namespace MVPS\Lumis\Framework\Support;

use PhpOption\Option;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Repository\Adapter\PutenvAdapter;

class Env
{
	/**
	 * Indicates if the putenv adapter is enabled.
	 *
	 * @var bool
	 */
	protected static bool $putenv = true;

	/**
	 * The environment repository instance.
	 *
	 * @var \Dotenv\Repository\RepositoryInterface|null
	 */
	protected static RepositoryInterface|null $repository = null;

	/**
	 * Disable the putenv adapter.
	 */
	public static function disablePutenv(): void
	{
		static::$putenv = false;
		static::$repository = null;
	}

	/**
	 * Enable the putenv adapter.
	 */
	public static function enablePutenv(): void
	{
		static::$putenv = true;
		static::$repository = null;
	}

	/**
	 * Gets the value of an environment variable.
	 */
	public static function get(string $key, mixed $default = null): mixed
	{
		return Option::fromValue(static::getRepository()->get($key))
			->map(function ($value) {
				switch (strtolower($value)) {
					case 'true':
					case '(true)':
						return true;
					case 'false':
					case '(false)':
						return false;
					case 'empty':
					case '(empty)':
						return '';
					case 'null':
					case '(null)':
						return null;
				}

				if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
					return $matches[2];
				}

				return $value;
			})
			->getOrCall(fn () => value($default));
	}

	/**
	 * Get the environment repository instance.
	 */
	public static function getRepository(): RepositoryInterface
	{
		if (static::$repository === null) {
			$builder = RepositoryBuilder::createWithDefaultAdapters();

			if (static::$putenv) {
				$builder = $builder->addAdapter(PutenvAdapter::class);
			}

			static::$repository = $builder->immutable()->make();
		}

		return static::$repository;
	}
}
