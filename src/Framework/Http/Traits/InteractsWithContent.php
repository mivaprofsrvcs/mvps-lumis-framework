<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use ArrayObject;
use JsonSerializable;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;
use stdClass;

trait InteractsWithContent
{
	/**
	 * Transform the given content to JSON.
	 */
	public function morphToJson(mixed $content): string|false
	{
		if ($content instanceof Jsonable) {
			return $content->toJson();
		} elseif ($content instanceof Arrayable) {
			return json_encode($content->toArray());
		}

		return json_encode($content);
	}

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
}
