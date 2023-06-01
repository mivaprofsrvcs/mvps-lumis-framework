<?php

namespace MVPS\Lumis\Framework\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
}
