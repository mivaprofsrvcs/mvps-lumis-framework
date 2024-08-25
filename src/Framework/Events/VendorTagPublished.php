<?php

namespace MVPS\Lumis\Framework\Events;

class VendorTagPublished
{
	/**
	 * The publishable paths registered by the tag.
	 *
	 * @var array
	 */
	public array $paths;

	/**
	 * The vendor tag that was published.
	 *
	 * @var string
	 */
	public string $tag;

	/**
	 * Create a new vendor tag published event instance.
	 */
	public function __construct(string $tag, array $paths)
	{
		$this->tag = $tag;
		$this->paths = $paths;
	}
}
