<?php

namespace MVPS\Lumis\Framework\Database\Schema;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as IlluminateBuilder;
use MVPS\Lumis\Framework\Container\Container;

class Builder extends IlluminateBuilder
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function createBlueprint($table, Closure|null $callback = null): Blueprint
	{
		$prefix = $this->connection->getConfig('prefix_indexes')
			? $this->connection->getConfig('prefix')
			: '';

		if (isset($this->resolver)) {
			return call_user_func($this->resolver, $table, $callback, $prefix);
		}

		return Container::getInstance()->make(Blueprint::class, compact('table', 'callback', 'prefix'));
	}
}
