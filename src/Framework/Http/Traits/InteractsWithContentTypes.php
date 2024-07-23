<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use MVPS\Lumis\Framework\Support\Str;

trait InteractsWithContentTypes
{
	/**
	 * Determines whether the current requests accepts a given content type.
	 */
	public function accepts(string|array $contentTypes): bool
	{
		$accepts = $this->getAcceptableContentTypes();

		if (count($accepts) === 0) {
			return true;
		}

		$types = (array) $contentTypes;

		foreach ($accepts as $accept) {
			if ($accept === '*/*' || $accept === '*') {
				return true;
			}

			foreach ($types as $type) {
				$accept = strtolower($accept);

				$type = strtolower($type);

				if ($this->matchesType($accept, $type) || $accept === strtok($type, '/') . '/*') {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determine if the current request accepts any content type.
	 */
	public function acceptsAnyContentType(): bool
	{
		$acceptable = $this->getAcceptableContentTypes();

		return count($acceptable) === 0 || (
			isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
		);
	}

	/**
	 * Determines whether a request accepts HTML.
	 */
	public function acceptsHtml(): bool
	{
		return $this->accepts('text/html');
	}

	/**
	 * Determine if the given content types match.
	 */
	public static function matchesType(string $actual, string $type): bool
	{
		if ($actual === $type) {
			return true;
		}

		$split = explode('/', $actual);

		return isset($split[1]) &&
			preg_match('#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#', $type);
	}

	/**
	 * Determines whether a request accepts JSON.
	 */
	public function acceptsJson(): bool
	{
		return $this->accepts('application/json');
	}

	/**
	 * Determine if the current request probably expects a JSON response.
	 */
	public function expectsJson(): bool
	{
		return ($this->ajax() && ! $this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
	}

	/**
	 * Get the data format expected in the response.
	 */
	public function format(string $default = 'html'): string
	{
		foreach ($this->getAcceptableContentTypes() as $type) {
			$format = $this->getFormat($type);

			if ($format) {
				return $format;
			}
		}

		return $default;
	}

	/**
	 * Determine if the request is sending JSON.
	 */
	public function isJson(): bool
	{
		return Str::contains($this->header('Content-Type', ''), ['/json', '+json']);
	}

	/**
	 * Return the most suitable content type from the given array based on content negotiation.
	 */
	public function prefers(string|array $contentTypes): string|null
	{
		$accepts = $this->getAcceptableContentTypes();

		$contentTypes = (array) $contentTypes;

		foreach ($accepts as $accept) {
			if (in_array($accept, ['*/*', '*'])) {
				return $contentTypes[0];
			}

			foreach ($contentTypes as $contentType) {
				$type = $contentType;

				if (! is_null($mimeType = $this->getMimeType($contentType))) {
					$type = $mimeType;
				}

				$accept = strtolower($accept);

				$type = strtolower($type);

				if ($this->matchesType($type, $accept) || $accept === strtok($type, '/') . '/*') {
					return $contentType;
				}
			}
		}
	}

	/**
	 * Determine if the current request is asking for JSON.
	 */
	public function wantsJson(): bool
	{
		$acceptable = $this->getAcceptableContentTypes();

		return isset($acceptable[0]) && Str::contains(strtolower($acceptable[0]), ['/json', '+json']);
	}
}
