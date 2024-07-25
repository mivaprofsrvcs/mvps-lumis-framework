<?php

namespace MVPS\Lumis\Framework\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

class Model extends IlluminateModel
{
	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected static $dispatcher;
}
