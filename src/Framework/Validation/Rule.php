<?php

namespace MVPS\Lumis\Framework\Validation;

use Illuminate\Validation\Rule as ValidationRule;
use MVPS\Lumis\Framework\Validation\Rules\Can;
use MVPS\Lumis\Framework\Validation\Rules\Enum;
use MVPS\Lumis\Framework\Validation\Rules\File;

class Rule extends ValidationRule
{
	/**
	 * {@inheritdoc}
	 *
	 * @return \MVPS\Lumis\Framework\Validation\Rules\Can
	 */
	public static function can($ability, ...$arguments)
	{
		return new Can($ability, $arguments);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return \MVPS\Lumis\Framework\Validation\Rules\Enum
	 */
	public static function enum($type)
	{
		return new Enum($type);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return \MVPS\Lumis\Framework\Validation\Rules\File
	 */
	public static function file()
	{
		return new File;
	}
}
