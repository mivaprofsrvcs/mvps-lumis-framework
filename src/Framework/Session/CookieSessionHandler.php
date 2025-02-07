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

namespace MVPS\Lumis\Framework\Session;

use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Contracts\Cookie\QueueingFactory as CookieJar;
use MVPS\Lumis\Framework\Http\Request;
use SessionHandlerInterface;

class CookieSessionHandler implements SessionHandlerInterface
{
	use InteractsWithTime;

	/**
	 * The cookie jar instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Cookie\Factory
	 */
	protected CookieJar $cookie;

	/**
	 * Indicates whether the session should be expired when the browser closes.
	 *
	 * @var bool
	 */
	protected bool $expireOnClose;

	/**
	 * The number of minutes the session should be valid.
	 *
	 * @var int
	 */
	protected int $minutes;

	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request|null $request = null;

	/**
	 * Create a new cookie session handler instance.
	 */
	public function __construct(CookieJar $cookie, int $minutes, bool $expireOnClose = false)
	{
		$this->cookie = $cookie;
		$this->minutes = $minutes;
		$this->expireOnClose = $expireOnClose;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): bool
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function destroy($sessionId): bool
	{
		$this->cookie->queue($this->cookie->forget($sessionId));

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function gc($lifetime): int
	{
		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function open($savePath, $sessionName): bool
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($sessionId): string|false
	{
		$value = $this->request->cookies->get($sessionId) ?: '';

		$decoded = json_decode($value, true);

		if (
			! is_null($decoded) &&
			is_array($decoded) &&
			isset($decoded['expires']) &&
			$this->currentTime() <= $decoded['expires']
		) {
			return $decoded['data'];
		}

		return '';
	}

	/**
	 * Set the request instance.
	 */
	public function setRequest(Request $request): static
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($sessionId, $data): bool
	{
		$this->cookie->queue($sessionId, json_encode([
			'data' => $data,
			'expires' => $this->availableAt($this->minutes * 60),
		]), $this->expireOnClose ? 0 : $this->minutes);

		return true;
	}
}
