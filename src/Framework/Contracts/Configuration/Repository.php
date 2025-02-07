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

namespace MVPS\Lumis\Framework\Contracts\Configuration;

interface Repository
{
	/**
	 * Get all of the configuration items for the application.
	 */
	public function all(): array;

	/**
	 * Get the specified configuration value.
	 */
	public function get(array|string $key, mixed $default = null): mixed;

	/**
	 * Determine if the given configuration value exists.
	 */
	public function has(string $key): bool;

	/**
	 * Prepend a value onto an array configuration value.
	 */
	public function prepend(string $key, mixed $value): void;

	/**
	 * Push a value onto an array configuration value.
	 */
	public function push(string $key, mixed $value): void;

	/**
	 * Set a given configuration value.
	 */
	public function set(array|string $key, mixed $value = null): void;
}
