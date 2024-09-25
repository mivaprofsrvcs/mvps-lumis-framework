<?php

namespace MVPS\Lumis\Framework\Http\Exceptions;

use MVPS\Lumis\Framework\Contracts\Http\RequestException;
use UnexpectedValueException;

class SuspiciousOperationException extends UnexpectedValueException implements RequestException
{
}
