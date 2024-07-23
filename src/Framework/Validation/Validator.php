<?php

namespace MVPS\Lumis\Framework\Validation;

use Illuminate\Validation\Validator as IlluminateValidator;
use MVPS\Lumis\Framework\Contracts\Validation\Validator as ValidatorContract;

class Validator extends IlluminateValidator implements ValidatorContract
{
}
