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
