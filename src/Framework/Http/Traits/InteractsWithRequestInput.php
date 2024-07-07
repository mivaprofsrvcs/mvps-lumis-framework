<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use Carbon\Carbon;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Support\Stringable;
use stdClass;

trait InteractsWithRequestInput
{
	/**
	 * Determine if the request contains a non-empty value for any of the given inputs.
	 */
	public function anyFilled(string|array $keys): bool
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		foreach ($keys as $key) {
			if ($this->filled($key)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get input form the request as a boolean value. Returns true when value
	 * is "1", "true", "on", and "yes". Otherwise, returns false.
	 */
	public function boolean(string|null $key = null, bool $default = false): bool
	{
		return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Retrieve input from the request as a collection.
	 */
	public function collection(array|string|null $key = null): Collection
	{
		return collection(is_array($key) ? $this->only($key) : $this->input($key));
	}

	/**
	 * Get input from the request as a Carbon instance.
	 */
	public function date(string $key, string|null $format = null, string|null $tz = null): Carbon|null
	{
		if ($this->isNotFilled($key)) {
			return null;
		}

		if (is_null($format)) {
			return Carbon::parse($this->input($key), $tz);
		}

		return Carbon::createFromFormat($format, $this->input($key), $tz);
	}

	/**
	 * Get all of the request input except for a specified array of items.
	 */
	public function except(mixed $keys): array
	{
		$keys = is_array($keys) ? $keys : func_get_args();
		$results = $this->input();

		Arr::forget($results, $keys);

		return $results;
	}

	/**
	 * Determine if the request contains a non-empty value for an input item.
	 */
	public function filled(string|array $key): bool
	{
		$keys = is_array($key) ? $key : func_get_args();

		foreach ($keys as $value) {
			if ($this->isEmptyString($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request contains a given input item key.
	 */
	public function has(string|array $key): bool
	{
		$input = $this->input();
		$keys = is_array($key) ? $key : func_get_args();

		foreach ($keys as $value) {
			if (! Arr::has($input, $value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request contains any of the given inputs.
	 */
	public function hasAny(string|array $keys): bool
	{
		$input = $this->input();
		$keys = is_array($keys) ? $keys : func_get_args();

		return Arr::hasAny($input, $keys);
	}

	/**
	 * Retrieve a header from the request.
	 */
	public function header(string|null $key = null, string|array|null $default = null): string|array|null
	{
		if (is_null($key)) {
			return $this->getHeaders();
		}

		if (! $this->hasHeader($key)) {
			return $default ?? '';
		}

		return $this->getHeaderLine($key);
	}

	/**
	 * Get all of the input and files for the request, or a specific input item with the given key.
	 */
	public function input(string|null $key = null, mixed $default = null): mixed
	{
		$input = array_merge(
			(array) $this->getParsedBody(),
			$this->getQueryParams(),
			$this->getUploadedFiles()
		);

		return data_get($input, $key, $default);
	}

	/**
	 * Get all of the query string items for the request, or a specific query string item with the given key.
	 */
	public function query(string|null $key = null, mixed $default = null): mixed
	{
		return data_get($this->getQueryParams(), $key, $default);
	}

	/**
	 * Get the query string for the request.
	 */
	public function queryString(): string
	{
		return http_build_query($this->getQueryParams());
	}

	/**
	 * Determine if the given input key is an empty string.
	 */
	protected function isEmptyString(string $key): bool
	{
		$value = $this->input($key);

		return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
	}

	/**
	 * Determine if the request contains an empty value for an input item.
	 */
	public function isNotFilled(string|array $key): bool
	{
		$keys = is_array($key) ? $key : func_get_args();

		foreach ($keys as $value) {
			if (! $this->isEmptyString($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request is missing a given input item key.
	 */
	public function missing(string|array $key): bool
	{
		return ! $this->has(
			is_array($key) ? $key : func_get_args()
		);
	}

	/**
	 * Get a subset containing the provided keys with values from the request input data.
	 */
	public function only(mixed $keys): array
	{
		$input = $this->input();
		$placeholder = new stdClass;
		$results = [];

		foreach (is_array($keys) ? $keys : func_get_args() as $key) {
			$value = data_get($input, $key, $placeholder);

			if ($value !== $placeholder) {
				Arr::set($results, $key, $value);
			}
		}

		return $results;
	}

	/**
	 * Retrieve input from the request as a Stringable instance.
	 */
	public function string(string $key, mixed $default = null): Stringable
	{
		return stringable($this->input($key, $default));
	}

	/**
	 * Apply the callback if the request contains a non-empty value for the given input item key.
	 */
	public function whenFilled(string $key, callable $callback, callable|null $default = null): mixed
	{
		if ($this->filled($key)) {
			return $callback(data_get($this->input(), $key)) ?: $this;
		}

		if ($default) {
			return $default();
		}

		return $this;
	}

	/**
	 * Apply the callback if the request contains the given input item key.
	 */
	public function whenHas(string $key, callable $callback, callable|null $default = null): mixed
	{
		if ($this->has($key)) {
			return $callback(data_get($this->input(), $key)) ?: $this;
		}

		if ($default) {
			return $default();
		}

		return $this;
	}

	/**
	 * Apply the callback if the request is missing the given input item key.
	 */
	public function whenMissing(string $key, callable $callback, callable|null $default = null): mixed
	{
		if ($this->missing($key)) {
			return $callback(data_get($this->input(), $key)) ?: $this;
		}

		if ($default) {
			return $default();
		}

		return $this;
	}
}
