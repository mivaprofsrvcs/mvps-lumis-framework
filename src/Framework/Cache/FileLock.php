<?php

namespace MVPS\Lumis\Framework\Cache;

class FileLock extends CacheLock
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function acquire(): bool
	{
		return $this->store->add($this->name, $this->owner, $this->seconds);
	}
}
