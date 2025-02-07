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

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\FileViewFinder as IlluminateFileViewFinder;
use MVPS\Lumis\Framework\Contracts\View\ViewFinder;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class FileViewFinder extends IlluminateFileViewFinder implements ViewFinder
{
	/**
	 * The filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new file view finder instance.
	 */
	public function __construct(Filesystem $files, array $paths, array|null $extensions = null)
	{
		parent::__construct($files, $paths, $extensions);
	}
}
