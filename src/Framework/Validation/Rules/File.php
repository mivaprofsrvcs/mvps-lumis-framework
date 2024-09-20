<?php

namespace MVPS\Lumis\Framework\Validation\Rules;

use Illuminate\Validation\Rules\File as ValidationRulesFile;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;

class File extends ValidationRulesFile
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function fail($messages)
	{
		$messages = collection(Arr::wrap($messages))
			->map(fn ($message) => $this->validator->getValidationMessage(
				Str::replaceStart('validation.', '', $message)
			))
			->all();

		$this->messages = array_merge($this->messages, $messages);

		return false;
	}
}
