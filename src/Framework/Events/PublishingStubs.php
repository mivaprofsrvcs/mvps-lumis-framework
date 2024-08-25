<?php

namespace MVPS\Lumis\Framework\Events;

use MVPS\Lumis\Framework\Events\Traits\Dispatchable;

class PublishingStubs
{
	use Dispatchable;

	/**
	 * The stubs being published.
	 *
	 * @var array
	 */
	public array $stubs = [];

	/**
	 * Create a new publishing stubs event instance.
	 */
	public function __construct(array $stubs)
	{
		$this->stubs = $stubs;
	}

	/**
	 * Add a new stub to be published.
	 */
	public function add(string $path, string $name): static
	{
		$this->stubs[$path] = $name;

		return $this;
	}
}
