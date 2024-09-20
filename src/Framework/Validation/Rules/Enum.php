<?php

namespace MVPS\Lumis\Framework\Validation\Rules;

use Illuminate\Validation\Rules\Enum as ValidationRulesEnum;

class Enum extends ValidationRulesEnum
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function message()
	{
		$message = $this->validator->getValidationMessage('enum');

		return $message === 'enum'
			? ['The selected :attribute is invalid.']
			: $message;
	}
}
