<?php

namespace MVPS\Lumis\Framework\Cache;

use Illuminate\Contracts\Cache\Store;

abstract class TaggableStore implements Store
{
	/**
	 * Begin executing a new tags operation.
	 */
	public function tags(mixed $names): TaggedCache
	{
		return new TaggedCache(
			$this,
			new TagSet($this, is_array($names) ? $names : func_get_args())
		);
	}
}
