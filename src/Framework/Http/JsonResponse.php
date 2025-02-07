<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Http;

use ArrayObject;
use InvalidArgumentException;
use JsonSerializable;
use MVPS\Lumis\Framework\Contracts\Support\Arrayable;
use MVPS\Lumis\Framework\Contracts\Support\Jsonable;

class JsonResponse extends Response
{
	/**
	 * Default JSON encoding flags for enhanced security.
	 *
	 * Includes escaping of special characters for protection against XSS attacks.
	 *
	 * @var int
	 */
	public const DEFAULT_ENCODING_OPTIONS = JSON_HEX_TAG
		| JSON_HEX_APOS
		| JSON_HEX_AMP
		| JSON_HEX_QUOT
		| JSON_UNESCAPED_SLASHES;

	/**
	 * The callback function for custom JSON encoding.
	 *
	 * @var string|null
	 */
	protected string|null $callback = null;

	/**
	 * Encoding options for JSON serialization.
	 *
	 * @var int
	 */
	protected int $encodingOptions;

	/**
	* The data to be encoded as JSON.
	*
	* Can be an array, object, or scalar value.
	*
	* @var mixed
	*/
	private mixed $data;

	/**
	 * Create a new JSON HTTP response instance.
	 */
	public function __construct(
		mixed $data = null,
		int $status = 200,
		array $headers = [],
		int $encodingOptions = 0
	) {
		$this->encodingOptions = $encodingOptions;

		parent::__construct('', $status, $headers);

		$this->setData($data ?? new ArrayObject);
	}

	/**
	 * Creates a JSON response instance from a JSON string.
	 */
	public static function fromJsonString(string|null $data = null, int $status = 200, array $headers = []): static
	{
		return new static($data, $status, $headers, 0);
	}

	/**
	 * Retrieves the decoded JSON response content.
	 */
	public function getData($assoc = false, $depth = 512): mixed
	{
		return json_decode($this->data, $assoc, $depth);
	}

	/**
	 * Retrieves the JSON encoding options.
	 */
	public function getEncodingOptions(): int
	{
		return $this->encodingOptions;
	}

	/**
	 * Determine if a JSON encoding option is set.
	 */
	public function hasEncodingOption(int $option): bool
	{
		return (bool) ($this->encodingOptions & $option);
	}

	/**
	 * Determine if an error occurred during JSON encoding.
	 */
	protected function hasValidJson(int $jsonError): bool
	{
		if ($jsonError === JSON_ERROR_NONE) {
			return true;
		}

		return $this->hasEncodingOption(JSON_PARTIAL_OUTPUT_ON_ERROR) && in_array($jsonError, [
			JSON_ERROR_RECURSION,
			JSON_ERROR_INF_OR_NAN,
			JSON_ERROR_UNSUPPORTED_TYPE,
		]);
	}

	/**
	 * Sets the JSONP callback.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setCallback(string|null $callback): static
	{
		if (! is_null($callback)) {
			$pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*(?:\[(?:"(?:\\\.|[^"\\\])*"|\'(?:\\\.|[^\'\\\])*\'|\d+)\])*?$/u';

			$reserved = [
				'break',
				'do',
				'instanceof',
				'typeof',
				'case',
				'else',
				'new',
				'var',
				'catch',
				'finally',
				'return',
				'void',
				'continue',
				'for',
				'switch',
				'while',
				'debugger',
				'function',
				'this',
				'with',
				'default',
				'if',
				'throw',
				'delete',
				'in',
				'try',
				'class',
				'enum',
				'extends',
				'super',
				 'const',
				'export',
				'import',
				'implements',
				'let',
				'private',
				'public',
				'yield',
				'interface',
				'package',
				'protected',
				'static',
				'null',
				'true',
				'false',
			];

			$parts = explode('.', $callback);

			foreach ($parts as $part) {
				if (! preg_match($pattern, $part) || in_array($part, $reserved, true)) {
					throw new InvalidArgumentException('The callback name is not valid.');
				}
			}
		}

		$this->callback = $callback;

		return $this->update();
	}

	/**
	 * Sets the data to be encoded as JSON.
	 */
	public function setData(mixed $data = []): static
	{
		$this->original = $data;

		// Clear json_last_error()
		json_decode('[]');

		$this->data = match (true) {
			$data instanceof Jsonable => $data->toJson($this->encodingOptions),
			$data instanceof JsonSerializable => json_encode($data->jsonSerialize(), $this->encodingOptions),
			$data instanceof Arrayable => json_encode($data->toArray(), $this->encodingOptions),
			default => json_encode($data, $this->encodingOptions),
		};

		if (! $this->hasValidJson(json_last_error())) {
			throw new InvalidArgumentException(json_last_error_msg());
		}

		return $this->update();
	}

	/**
	 * Sets the JSON encoding options.
	 */
	public function setEncodingOptions(int $encodingOptions): static
	{
		$this->encodingOptions = $encodingOptions;

		return $this->setData($this->getData());
	}

	/**
	 * Prepares the JSON response by setting headers and content.
	 *
	 * Determines the appropriate content type and formats the
	 * response data based on the callback or raw data.
	 */
	protected function update(): static
	{
		if (! is_null($this->callback)) {
			// Not using application/javascript for compatibility reasons with older browsers.
			$this->headerBag->set('Content-Type', 'text/javascript');

			return $this->setContent(sprintf('/**/%s(%s);', $this->callback, $this->data));
		}

		// Preserve custom Content-Type headers, only set the default
		// 'application/json' if not already defined.
		if (! $this->headerBag->has('Content-Type') || $this->headerBag->get('Content-Type') === 'text/javascript') {
			$this->headerBag->set('Content-Type', 'application/json');
		}

		return $this->setContent($this->data);
	}

	/**
	 * Sets the callback function for JSONP output.
	 */
	public function withCallback(string|null $callback = null): static
	{
		return $this->setCallback($callback);
	}
}
