<?php

namespace MVPS\Lumis\Framework\Contracts\Session;

use SessionHandlerInterface;

interface ExistenceAware
{
	/**
	 * Set the existence state for the session.
	 */
	public function setExists(bool $value): SessionHandlerInterface;
}
