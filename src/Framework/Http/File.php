<?php

namespace MVPS\Lumis\Framework\Http;

use MVPS\Lumis\Framework\Http\Traits\FileHelpers;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class File extends SymfonyFile
{
	use FileHelpers;
}
