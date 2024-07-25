<?php

namespace MVPS\Lumis\Framework\Database;

use Illuminate\Database\Connection as IlluminateConnection;
use MVPS\Lumis\Framework\Contracts\Database\Connection as ConnectionContract;

class Connection extends IlluminateConnection implements ConnectionContract
{
}
