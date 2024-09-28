<?php

namespace MVPS\Lumis\Framework\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Reflector;
use MVPS\Lumis\Framework\Contracts\Container\Container;
use MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException;
use MVPS\Lumis\Framework\Support\Str;

class ImplicitRouteBinding
{
	/**
	 * Return the parameter name if it exists in the given parameters.
	 */
	protected static function getParameterName(string $name, array $parameters): string|null
	{
		if (array_key_exists($name, $parameters)) {
			return $name;
		}

		$snakedName = Str::snake($name);

		if (array_key_exists($snakedName, $parameters)) {
			return $snakedName;
		}

		return null;
	}

	/**
	 * Resolve the Backed Enums route bindings for the route.
	 *
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException
	 */
	protected static function resolveBackedEnumsForRoute(Route $route, array $parameters): Route
	{
		foreach ($route->signatureParameters(['backedEnum' => true]) as $parameter) {
			if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
				continue;
			}

			$parameterValue = $parameters[$parameterName];

			if (is_null($parameterValue)) {
				continue;
			}

			$backedEnumClass = $parameter->getType()?->getName();

			$backedEnum = $parameterValue instanceof $backedEnumClass
				? $parameterValue
				: $backedEnumClass::tryFrom((string) $parameterValue);

			if (is_null($backedEnum)) {
				throw new BackedEnumCaseNotFoundException($backedEnumClass, $parameterValue);
			}

			$route->setParameter($parameterName, $backedEnum);
		}

		return $route;
	}

	/**
	 * Resolve the implicit route bindings for the given route.
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\BackedEnumCaseNotFoundException
	 */
	public static function resolveForRoute(Container $container, Route $route): void
	{
		$parameters = $route->parameters();

		$route = static::resolveBackedEnumsForRoute($route, $parameters);

		foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $parameter) {
			$parameterName = static::getParameterName($parameter->getName(), $parameters);

			if (! $parameterName) {
				continue;
			}

			$parameterValue = $parameters[$parameterName];

			if ($parameterValue instanceof UrlRoutable) {
				continue;
			}

			$instance = $container->make(Reflector::getParameterClassName($parameter));

			$parent = $route->parentOfParameter($parameterName);

			$routeBindingMethod = $route->allowsTrashedBindings() &&
				in_array(SoftDeletes::class, class_uses_recursive($instance))
					? 'resolveSoftDeletableRouteBinding'
					: 'resolveRouteBinding';

			if (
				$parent instanceof UrlRoutable &&
				! $route->preventsScopedBindings() &&
				($route->enforcesScopedBindings() || array_key_exists($parameterName, $route->bindingFields()))
			) {
				$childRouteBindingMethod = $route->allowsTrashedBindings()
					&& in_array(SoftDeletes::class, class_uses_recursive($instance))
						? 'resolveSoftDeletableChildRouteBinding'
						: 'resolveChildRouteBinding';

				$model = $parent->{$childRouteBindingMethod}(
					$parameterName,
					$parameterValue,
					$route->bindingFieldFor($parameterName)
				);

				if (! $model) {
					throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
				}
			} elseif (
				! $model = $instance->{$routeBindingMethod}($parameterValue, $route->bindingFieldFor($parameterName))
			) {
				throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
			}

			$route->setParameter($parameterName, $model);
		}
	}
}
