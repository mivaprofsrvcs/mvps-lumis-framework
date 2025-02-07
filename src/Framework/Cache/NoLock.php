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

namespace MVPS\Lumis\Framework\Cache;

class NoLock extends Lock
{
	/**
	 * {@inheritdoc}
	 */
	public function acquire(): bool
	{
		return true;
	}

	/**
	 * Releases this lock in disregard of ownership.
	 */
	public function forceRelease(): null
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getCurrentOwner()
	{
		return $this->owner;
	}

	/**
	 * {@inheritdoc}
	 */
	public function release(): bool
	{
		return true;
	}
}
