<?php

namespace MVPS\Lumis\Framework\Routing;

use DomainException;
use LogicException;

class CompiledRoute
{
	/**
	 * The route path pattern to match.
	 *
	 * @var string
	 */
	protected string $path = '/';

	/**
	 * The regular expression to use to match the route.
	 *
	 * @var string
	 */
	protected string $regex = '';

	/**
	 * The list of tokens to use to match the route.
	 *
	 * @var array
	 */
	protected array $tokens = [];

	/**
	 * The list of route path variables.
	 *
	 * @var array
	 */
	protected array $variables = [];

	/**
	 * Defines the characters that are automatically considered separators.
	 */
	public const SEPARATORS = '/,;.:-_~+*=@|';

	/**
	 * The maximum supported length of a PCRE subpattern name.
	 */
	public const VARIABLE_MAXIMUM_LENGTH = 32;

	/**
	 * Create a new compiled route instance.
	 */
	public function __construct(string $path)
	{
		$this->setPath($path);
	}

	/**
	 * Compile the compiled route instance.
	 */
	public function compile(): static
	{
		$compiledResult = $this->compilePattern($this->getPath());

		$this->regex = $compiledResult['regex'];
		$this->tokens = $compiledResult['tokens'];
		$this->variables = $compiledResult['variables'];

		return $this;
	}

	/**
	 * Compile the provided pattern.
	 *
	 * @throws DomainException
	 * @throws LogicException
	 */
	protected function compilePattern(string $pattern): array
	{
		$defaultSeparator = '/';
		$matches = [];
		$pos = 0;
		$tokens = [];
		$variables = [];

		preg_match_all('/\{(!)?([\w\x80-\xFF]+)\}/', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		foreach ($matches as $match) {
			$variableName = $match[2][0];
			$isImportant = $match[1][1] >= 0;
			$precedingText = substr($pattern, $pos, $match[0][1] - $pos);
			$pos = $match[0][1] + strlen($match[0][0]);
			$precedingChar = '';

			if (strlen($precedingText)) {
				preg_match('/.$/u', $precedingText, $precedingChar);

				$precedingChar = $precedingChar[0];
			}

			$isSeparator = $precedingChar !== '' && str_contains(static::SEPARATORS, $precedingChar);

			// A PCRE subpattern name must start with a non-digit.
			if (preg_match('/^\d/', $variableName)) {
				throw new DomainException(
					sprintf(
						'Variable name "%s" cannot start with a digit in route pattern "%s".',
						$variableName,
						$pattern
					)
				);
			}

			if (in_array($variableName, $variables)) {
				throw new LogicException(
					sprintf(
						'Route pattern "%s" cannot reference variable name "%s" more than once.',
						$pattern,
						$variableName
					)
				);
			}

			if (strlen($variableName) > static::VARIABLE_MAXIMUM_LENGTH) {
				throw new DomainException(
					sprintf(
						'Variable name "%s" cannot be longer than %d characters in route pattern "%s".',
						$variableName,
						static::VARIABLE_MAXIMUM_LENGTH,
						$pattern
					)
				);
			}

			if ($isSeparator && $precedingText !== $precedingChar) {
				$tokens[] = [
					'text',
					substr($precedingText, 0, -strlen($precedingChar)),
				];
			} elseif (! $isSeparator && $precedingText !== '') {
				$tokens[] = [
					'text',
					$precedingText,
				];
			}

			$followingPattern = (string) substr($pattern, $pos);

			$nextSeparator = $this->findNextSeparator($followingPattern);

			$regexp = sprintf(
				'[^%s%s]+',
				preg_quote($defaultSeparator),
				$defaultSeparator !== $nextSeparator && $nextSeparator !== '' ? preg_quote($nextSeparator) : ''
			);

			if (
				$followingPattern === ''
				|| ($nextSeparator !== '' && ! preg_match('#^\{[\w\x80-\xFF]+\}#', $followingPattern))
			) {
				$regexp .= '+';
			}

			$token = [
				'variable',
				$isSeparator ? $precedingChar : '',
				$regexp,
				$variableName,
			];

			if ($isImportant) {
				array_push($token, false, true);
			}

			$tokens[] = $token;
			$variables[] = $variableName;
		}

		if ($pos < strlen($pattern)) {
			$tokens[] = [
				'text',
				substr($pattern, $pos),
			];
		}

		$tokenCount = count($tokens);
		$regexp = '';

		for ($index = 0; $index < $tokenCount; ++$index) {
			$regexp .= $this->computeRegexp($tokens, $index);
		}

		$regexp = '{^' . $regexp . '$}sDu';

		for ($index = 0; $index < $tokenCount; ++$index) {
			if ($tokens[$index][0] === 'variable') {
				$tokens[$index][4] = true;
			}
		}

		return [
			'regex' => $regexp,
			'tokens' => array_reverse($tokens),
			'variables' => $variables,
		];
	}

	/**
	 * Computes the regexp used to match a specific token (static text or sub-pattern).
	 */
	protected function computeRegexp(array $tokens, int $index): string
	{
		$token = $tokens[$index];

		if ($token[0] === 'text') {
			return preg_quote($token[1]);
		}

		return sprintf('%s(?P<%s>%s)', preg_quote($token[1]), $token[3], $token[2]);
	}

	/**
	 * Find the next static character in the route pattern that will serve as
	 * a separator or empty string when none available).
	 */
	protected function findNextSeparator(string $pattern): string
	{
		if ($pattern === '') {
			return '';
		}

		$pattern = preg_replace('#\{[\w\x80-\xFF]+\}#', '', $pattern);

		if ($pattern === '') {
			return '';
		}

		preg_match('/^./u', $pattern, $pattern);

		return str_contains(static::SEPARATORS, $pattern[0]) ? $pattern[0] : '';
	}

	/**
	 * Get the The route path pattern.
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get the regular expression to use to match the route.
	 */
	public function getRegex(): string
	{
		return $this->regex;
	}

	/**
	 * Get the list of tokens to use to match the route.
	 */
	public function getTokens(): array
	{
		return $this->tokens;
	}

	/**
	 * Get the list of route path variables.
	 */
	public function getVariables(): array
	{
		return $this->variables;
	}

	/**
	 * Set the route path pattern.
	 */
	public function setPath(string $pattern): static
	{
		$this->path = '/' . ltrim(trim($pattern), '/');

		return $this;
	}
}
