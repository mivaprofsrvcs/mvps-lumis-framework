<?php

namespace MVPS\Lumis\Framework\Filesystem;

use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException;
use Throwable;

class Filesystem extends IlluminateFilesystem
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	#[\Override]
	public function get($path, $lock = false)
	{
		try {
			return parent::get($path, $lock);
		} catch (Throwable $e) {
			throw new FileNotFoundException($e->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	#[\Override]
	public function getRequire($path, array $data = [])
	{
		try {
			return parent::getRequire($path, $data);
		} catch (Throwable $e) {
			throw new FileNotFoundException($e->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	#[\Override]
	public function lines($path)
	{
		try {
			return parent::lines($path);
		} catch (Throwable $e) {
			throw new FileNotFoundException($e->getMessage());
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	#[\Override]
	public function requireOnce($path, array $data = [])
	{
		try {
			return parent::requireOnce($path, $data);
		} catch (Throwable $e) {
			throw new FileNotFoundException($e->getMessage());
		}
	}
}
