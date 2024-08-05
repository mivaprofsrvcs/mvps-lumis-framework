<?php

namespace MVPS\Lumis\Framework\Contracts\Cookie;

interface QueueingFactory extends Factory
{
	/**
	 * Get the cookies which have been queued for the next request.
	 */
	public function getQueuedCookies(): array;

	/**
	 * Queue a cookie to send with the next response.
	 */
	public function queue(mixed ...$parameters): void;

	/**
	 * Remove a cookie from the queue.

	 */
	public function unqueue(string $name, string|null $path = null): void;
}
