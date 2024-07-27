<?php

namespace MVPS\Lumis\Framework\Contracts\Exceptions\Renderer;

use Whoops\Exception\Frame;

interface RenderableOnEditor
{
	/**
	 * The frame to be used on the Editor.
	 */
	public function toEditor(): Frame;
}
