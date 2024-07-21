<?php

namespace MVPS\Lumis\Framework\View;

use Illuminate\View\InvokableComponentVariable as IlluminateInvokableComponentVariable;
use MVPS\Lumis\Framework\Contracts\Support\DeferringDisplayableValue;

class InvokableComponentVariable extends IlluminateInvokableComponentVariable implements DeferringDisplayableValue
{
}
