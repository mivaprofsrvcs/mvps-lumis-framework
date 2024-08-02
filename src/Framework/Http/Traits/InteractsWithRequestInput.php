<?php

namespace MVPS\Lumis\Framework\Http\Traits;

use Carbon\Carbon;
use Illuminate\Support\Traits\Dumpable;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Http\UploadedFile;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Stringable;
use SplFileInfo;
use stdClass;
use Symfony\Component\HttpFoundation\InputBag;

trait InteractsWithRequestInput
{
	use Dumpable;

	/**
	 * Get all of the input and files for the request.
	 */
	public function all(mixed $keys = null): array
	{
		$input = array_replace_recursive($this->input(), $this->allFiles());

		if (! $keys) {
			return $input;
		}

		$results = [];

		foreach (is_array($keys) ? $keys : func_get_args() as $key) {
			Arr::set($results, $key, Arr::get($input, $key));
		}

		return $results;
	}

	/**
	 * Get an array of all of the files on the request.
	 */
	public function allFiles(): array
	{
		$files = $this->fileBag->all();

		return $this->convertedFiles = $this->convertedFiles ?? $this->convertUploadedFiles($files);
	}

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
	 * Get the bearer token from the request headers.
	 */
	public function bearerToken(): string|null
	{
		$header = $this->header('Authorization', '');

		$position = strrpos($header, 'Bearer ');

		if ($position !== false) {
			$header = substr($header, $position + 7);

			return str_contains($header, ',')
				? strstr($header, ',', true)
				: $header;
		}
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
	public function collect(array|string|null $key = null): Collection
	{
		return collection(is_array($key) ? $this->only($key) : $this->input($key));
	}

	/**
	 * Convert the given array of Symfony UploadedFiles to custom
	 * Lumis UploadedFiles.
	 */
	protected function convertUploadedFiles(array $files): array
	{
		return array_map(
			function ($file) {
				if (is_null($file) || (is_array($file) && empty(array_filter($file)))) {
					return $file;
				}

				return is_array($file)
					? $this->convertUploadedFiles($file)
					: UploadedFile::createFromBase($file);
			},
			$files
		);
	}

	/**
	 * Retrieve a cookie from the request.
	 */
	public function cookie(string|null $key = null, string|array|null $default = null): string|array|null
	{
		return $this->retrieveItem('cookieBag', $key, $default);
	}

	/**
	 * Get input from the request as a Carbon instance.
	 *
	 * @throws \Carbon\Exceptions\InvalidFormatException
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
	 * Dump the items.
	 */
	public function dump(mixed $keys = []): static
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		dump(count($keys) > 0 ? $this->only($keys) : $this->all());

		return $this;
	}

	/**
	 * Retrieve input from the request as an enum.
	 *
	 * Attempts to convert the input value to the specified enum type using
	 * the `tryFrom` method. Returns null if the input is empty, the enum
	 * class is invalid, or the conversion fails.
	 */
	public function enum(string $key, $enumClass)
	{
		if (
			$this->isNotFilled($key) ||
			! enum_exists($enumClass) ||
			! method_exists($enumClass, 'tryFrom')
		) {
			return null;
		}

		return $enumClass::tryFrom($this->input($key));
	}

	/**
	 * Get all of the request input except for a specified array of items.
	 */
	public function except(mixed $keys): array
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		$results = $this->all();

		Arr::forget($results, $keys);

		return $results;
	}

	/**
	 * Determine if the request contains a given input item key.
	 */
	public function exists(string|array $key): bool
	{
		return $this->has($key);
	}

	/**
	 * Retrieve a file from the request.
	 */
	public function file(string|null $key = null, mixed $default = null): UploadedFile|array|null
	{
		return data_get($this->allFiles(), $key, $default);
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
	 * Retrieve input as a float value.
	 */
	public function float(string $key, float $default = 0.0): float
	{
		return floatval($this->input($key, $default));
	}

	/**
	 * Determine if the request contains a given input item key.
	 */
	public function has(string|array $key): bool
	{
		$keys = is_array($key) ? $key : func_get_args();

		$input = $this->all();

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
		$keys = is_array($keys) ? $keys : func_get_args();

		$input = $this->all();

		return Arr::hasAny($input, $keys);
	}

	/**
	 * Determine if a cookie is set on the request.
	 */
	public function hasCookie(string $key): bool
	{
		return ! is_null($this->cookie($key));
	}

	/**
	 * Determine if the uploaded data contains a file.
	 */
	public function hasFile(string $key): bool
	{
		$files = $this->file($key);

		if (! is_array($files)) {
			$files = [$files];
		}

		foreach ($files as $file) {
			if ($this->isValidFile($file)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve a header from the request.
	 */
	public function header(string|null $key = null, string|array|null $default = null): string|array|null
	{
		return $this->retrieveItem('headerBag', $key, $default);
	}

	/**
	 * Get all of the input and files for the request, or a specific input
	 * item with the given key.
	 */
	public function input(string|null $key = null, mixed $default = null): mixed
	{
		return data_get(
			$this->getInputSource()->all() + $this->queryBag->all(),
			$key,
			$default
		);
	}

	/**
	 * Retrieve input as an integer value.
	 */
	public function integer(string $key, int $default = 0): int
	{
		return intval($this->input($key, $default));
	}

	/**
	 * Check that the given file is a valid file instance.
	 */
	protected function isValidFile(mixed $file): bool
	{
		return $file instanceof SplFileInfo && $file->getPath() !== '';
	}

	/**
	 * Get all of the query string items for the request, or a specific query string item with the given key.
	 */
	public function query(string|null $key = null, mixed $default = null): mixed
	{
		return $this->retrieveItem('queryBag', $key, $default);
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
	 * Get the keys for all of the input and files.
	 */
	public function keys(): array
	{
		return array_merge(array_keys($this->input()), $this->fileBag->keys());
	}

	/**
	 * Determine if the request is missing a given input item key.
	 */
	public function missing(string|array $key): bool
	{
		return ! $this->has(is_array($key) ? $key : func_get_args());
	}

	/**
	 * Get a subset containing the provided keys with values from the request input data.
	 */
	public function only(mixed $keys): array
	{
		$results = [];

		$input = $this->all();

		$placeholder = new stdClass;

		foreach (is_array($keys) ? $keys : func_get_args() as $key) {
			$value = data_get($input, $key, $placeholder);

			if ($value !== $placeholder) {
				Arr::set($results, $key, $value);
			}
		}

		return $results;
	}

	/**
	 * Retrieve a request payload item from the request.
	 */
	public function post(string|null $key = null, string|array|null $default = null): string|array|null
	{
		return $this->retrieveItem('requestBag', $key, $default);
	}

	/**
	 * Retrieve a server variable from the request.
	 */
	public function server(string|null $key = null, string|array|null $default = null): string|array|null
	{
		return $this->retrieveItem('serverBag', $key, $default);
	}

	/**
	 * Retrieve input from the request as a Stringable instance.
	 */
	public function string(string $key, mixed $default = null): Stringable
	{
		return stringable($this->input($key, $default));
	}

	/**
	 * Retrieve a parameter item from a given source.
	 */
	protected function retrieveItem(string $source, string|null $key, string|array|null $default): string|array|null
	{
		if (is_null($key)) {
			return $this->{$source}->all();
		}

		if ($this->{$source} instanceof InputBag) {
			return $this->{$source}->all()[$key] ?? $default;
		}

		return $this->{$source}->get($key, $default);
	}

	/**
	 * Apply the callback if the request contains a non-empty value for the given input item key.
	 */
	public function whenFilled(string $key, callable $callback, callable|null $default = null): mixed
	{
		if ($this->filled($key)) {
			return $callback(data_get($this->all(), $key)) ?: $this;
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
			return $callback(data_get($this->all(), $key)) ?: $this;
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
			return $callback(data_get($this->all(), $key)) ?: $this;
		}

		if ($default) {
			return $default();
		}

		return $this;
	}
}
