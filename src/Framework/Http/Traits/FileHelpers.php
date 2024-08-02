<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use MVPS\Lumis\Framework\Support\Str;

trait FileHelpers
{
	/**
	 * The cache copy of the file's hash name.
	 *
	 * @var string|null
	 */
	protected string|null $hashName = null;

	/**
	 * Get the dimensions of the image (if applicable).
	 */
	public function dimensions(): array|null
	{
		return @getimagesize($this->getRealPath());
	}

	/**
	 * Get the file's extension.
	 */
	public function extension(): string
	{
		return $this->guessExtension();
	}

	/**
	 * Get a filename for the file.
	 */
	public function hashName(string|null $path = null): string
	{
		if ($path) {
			$path = rtrim($path, '/') . '/';
		}

		$hash = $this->hashName ?: $this->hashName = Str::random(40);

		if ($extension = $this->guessExtension()) {
			$extension = '.' . $extension;
		}

		return $path . $hash . $extension;
	}

	/**
	 * Get the fully qualified path to the file.
	 */
	public function path(): string
	{
		return $this->getRealPath();
	}
}
