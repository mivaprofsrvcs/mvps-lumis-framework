<?php

namespace MVPS\Lumis\Framework\Exceptions\Renderer;

use MVPS\Lumis\Framework\Contracts\Exceptions\Renderer\RenderlessEditor;
use MVPS\Lumis\Framework\Contracts\Exceptions\Renderer\RenderlessTrace;
use RuntimeException;

class NotSupportedYetException extends RuntimeException implements RenderlessEditor, RenderlessTrace
{
}
