<?php

namespace MVPS\Lumis\Framework\Support;

use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Reflector
{
	/**
	 * Get the class name of the given parameter's type.
	 */
	public static function getParameterClassName(ReflectionParameter $parameter): string|null
	{
		$type = $parameter->getType();

		if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
			return null;
		}

		return static::getTypeName($parameter, $type);
	}

	/**
	 * Get the class names of the given parameter's type, including union types.
	 */
	public static function getParameterClassNames(ReflectionParameter $parameter): array
	{
		$type = $parameter->getType();

		if (! $type instanceof ReflectionUnionType) {
			return array_filter([static::getParameterClassName($parameter)]);
		}

		$unionTypes = [];

		foreach ($type->getTypes() as $listedType) {
			if (! $listedType instanceof ReflectionNamedType || $listedType->isBuiltin()) {
				continue;
			}

			$unionTypes[] = static::getTypeName($parameter, $listedType);
		}

		return array_filter($unionTypes);
	}

	/**
	 * Get the given type's class name.
	 */
	protected static function getTypeName(ReflectionParameter $parameter, ReflectionNamedType $type): string
	{
		$name = $type->getName();

		if (! is_null($class = $parameter->getDeclaringClass())) {
			if ($name === 'self') {
				return $class->getName();
			}

			if ($name === 'parent') {
				$parent = $class->getParentClass();

				if ($parent) {
					return $parent->getName();
				}
			}
		}

		return $name;
	}

	/**
	 * This is a PHP 7.4 compatible implementation of is_callable.
	 */
	public static function isCallable(mixed $var, bool $syntaxOnly = false): bool
	{
		if (! is_array($var)) {
			return is_callable($var, $syntaxOnly);
		}

		if (! isset($var[0], $var[1]) || ! is_string($var[1] ?? null)) {
			return false;
		}

		if (
			$syntaxOnly &&
			(is_string($var[0]) || is_object($var[0])) &&
			is_string($var[1])
		) {
			return true;
		}

		$class = is_object($var[0]) ? get_class($var[0]) : $var[0];

		$method = $var[1];

		if (! class_exists($class)) {
			return false;
		}

		if (method_exists($class, $method)) {
			return (new ReflectionMethod($class, $method))->isPublic();
		}

		if (is_object($var[0]) && method_exists($class, '__call')) {
			return (new ReflectionMethod($class, '__call'))->isPublic();
		}

		if (! is_object($var[0]) && method_exists($class, '__callStatic')) {
			return (new ReflectionMethod($class, '__callStatic'))->isPublic();
		}

		return false;
	}

	/**
	 * Determine if the parameter's type is a Backed Enum with a string backing type.
	 */
	public static function isParameterBackedEnumWithStringBackingType(ReflectionParameter $parameter): bool
	{
		if (! $parameter->getType() instanceof ReflectionNamedType) {
			return false;
		}

		$backedEnumClass = $parameter->getType()?->getName();

		if (is_null($backedEnumClass)) {
			return false;
		}

		if (enum_exists($backedEnumClass)) {
			$reflectionBackedEnum = new ReflectionEnum($backedEnumClass);

			return $reflectionBackedEnum->isBacked()
				&& $reflectionBackedEnum->getBackingType()->getName() === 'string';
		}

		return false;
	}

	/**
	 * Determine if the parameter's type is a subclass of the given type.
	 */
	public static function isParameterSubclassOf(ReflectionParameter $parameter, string $className): bool
	{
		$paramClassName = static::getParameterClassName($parameter);

		return $paramClassName
			&& (class_exists($paramClassName) || interface_exists($paramClassName))
			&& (new ReflectionClass($paramClassName))->isSubclassOf($className);
	}
}
