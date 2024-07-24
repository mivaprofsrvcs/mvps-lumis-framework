<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use ArrayObject;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use stdClass;

trait InteractsWithContent
{
	/**
	 * Determine if the given content should be transformed into JSON.
	 */
	public function shouldBeJson(mixed $content): bool
	{
		return $content instanceof Arrayable ||
			$content instanceof Jsonable ||
			$content instanceof ArrayObject ||
			$content instanceof JsonSerializable ||
			$content instanceof stdClass ||
			is_array($content);
	}

	/**
	 * Transform the given content to JSON.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function transformToJson(mixed $content): string
	{
		try {
			return json_encode($content, JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new InvalidArgumentException(
				'Unable to encode data to JSON in ' . static::class . ': ' . $exception->getMessage(),
				0,
				$exception
			);
		}
	}
}
