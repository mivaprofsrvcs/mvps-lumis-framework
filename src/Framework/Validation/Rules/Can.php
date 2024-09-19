<?php

namespace MVPS\Lumis\Framework\Validation\Rules;

use Illuminate\Validation\Rules\Can as ValidationRulesCan;

class Can extends ValidationRulesCan
{
	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function message()
	{
		$message = $this->validator->getValidationMessage('can');

		return $message === 'can'
			? ['The :attribute field contains an unauthorized value.']
			: $message;
	}
}
