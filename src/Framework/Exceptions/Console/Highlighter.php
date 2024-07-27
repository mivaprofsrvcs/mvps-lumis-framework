<?php

namespace MVPS\Lumis\Framework\Exceptions\Console;

class Highlighter
{
	/**
	 * Symbol for marking the actual line in a diff.
	 *
	 * @var string
	 */
	public const ACTUAL_LINE_MARK = 'actual_line_mark';

	/**
	 * Arrow symbol for indicating code changes.
	 *
	 * @var string
	 */
	protected const ARROW_SYMBOL = '>';

	/**
	 * Arrow symbol for UTF-8 encoding.
	 *
	 * @var string
	 */
	protected const ARROW_SYMBOL_UTF8 = '➜';

	/**
	 * The default color theme.
	 */
	protected const DEFAULT_THEME = [
		self::TOKEN_STRING => 'red',
		self::TOKEN_COMMENT => 'yellow',
		self::TOKEN_KEYWORD => 'green',
		self::TOKEN_DEFAULT => 'default',
		self::TOKEN_HTML => 'cyan',

		self::ACTUAL_LINE_MARK => 'dark_gray',
		self::LINE_NUMBER => 'dark_gray',
		self::MARKED_LINE_NUMBER => 'dark_gray',
		self::LINE_NUMBER_DIVIDER => 'dark_gray',
	];

	/**
	 * Line delimiter for output formatting.
	 *
	 * @var string
	 */
	protected const DELIMITER = '|';

	/**
	 * Line delimiter for UTF-8 encoding ('▶').
	 *
	 * @var string
	 */
	protected const DELIMITER_UTF8 = '▕';

	/**
	 * Line number token type.
	 *
	 * @var string
	 */
	public const LINE_NUMBER = 'line_number';

	/**
	 * Token type for line number divider.
	 *
	 * @var string
	 */
	protected const LINE_NUMBER_DIVIDER = 'line_divider';

	/**
	 * Token type for marked line number.
	 *
	 * @var string
	 */
	protected const MARKED_LINE_NUMBER = 'marked_line';

	/**
	 * Space for unmarked lines.
	 *
	 * @var string
	 */
	protected const NO_MARK = '    ';

	/**
	 * The color theme.
	 *
	 * @var array
	 */
	protected const THEME = [
		self::TOKEN_STRING => ['light_gray'],
		self::TOKEN_COMMENT => ['dark_gray', 'italic'],
		self::TOKEN_KEYWORD => ['magenta', 'bold'],
		self::TOKEN_DEFAULT => ['default', 'bold'],
		self::TOKEN_HTML => ['blue', 'bold'],

		self::ACTUAL_LINE_MARK => ['red', 'bold'],
		self::LINE_NUMBER => ['dark_gray'],
		self::MARKED_LINE_NUMBER => ['italic', 'bold'],
		self::LINE_NUMBER_DIVIDER => ['dark_gray'],
	];

	/**
	 * Comment token type.
	 *
	 * @var string
	 */
	public const TOKEN_COMMENT = 'token_comment';

	/**
	 * Default token type.
	 *
	 * @var string
	 */
	public const TOKEN_DEFAULT = 'token_default';

	/**
	 * HTML token type.
	 *
	 * @var string
	 */
	public const TOKEN_HTML = 'token_html';

	/**
	 * Keyword token type.
	 *
	 * @var string
	 */
	public const TOKEN_KEYWORD = 'token_keyword';

	/**
	 * String token type.
	 *
	 * @var string
	 */
	public const TOKEN_STRING = 'token_string';

	/**
	 * Width of line number column.
	 *
	 * @var string
	 */
	protected const WIDTH = 3;

	/**
	 * The console color instance.
	 *
	 * @var \MVPS\Lumis\Framework\Exceptions\Console\Color
	 */
	protected Color $color;

	/**
	 * Line delimiter for output formatting.
	 *
	 * @var string
	 */
	protected string $delimiter = self::DELIMITER_UTF8;

	/**
	 * Arrow symbol for indicating code changes.
	 *
	 * @var string
	 */
	protected string $arrow = self::ARROW_SYMBOL_UTF8;

	/**
	 * Creates a new highlighter instance.
	 */
	public function __construct(Color|null $color = null, bool $utf8 = true)
	{
		$this->color = $color ?: new Color;

		$this->addThemes();

		if (! $utf8) {
			$this->delimiter = self::DELIMITER;
			$this->arrow = self::ARROW_SYMBOL;
		}

		$this->delimiter .= ' ';
	}

	/**
	 * Adds default and custom color themes to the color instance.
	 */
	protected function addThemes(): void
	{
		foreach (self::DEFAULT_THEME as $name => $styles) {
			if (! $this->color->hasTheme($name)) {
				$this->color->addTheme($name, $styles);
			}
		}

		foreach (self::THEME as $name => $styles) {
			$this->color->addTheme($name, $styles);
		}
	}

	/**
	 * Colors a line number based on the given style and length.
	 */
	protected function coloredLineNumber(string $style, int $i, int $length): string
	{
		return $this->color->apply(
			$style,
			str_pad((string) ($i + 1), $length, ' ', STR_PAD_LEFT)
		);
	}

	/**
	 * Applies color styles to each line of tokens.
	 */
	protected function colorLines(array $tokenLines): array
	{
		$lines = [];

		foreach ($tokenLines as $lineCount => $tokenLine) {
			$line = '';

			foreach ($tokenLine as $token) {
				[$tokenType, $tokenValue] = $token;

				if ($this->color->hasTheme($tokenType)) {
					$line .= $this->color->apply($tokenType, $tokenValue);
				} else {
					$line .= $tokenValue;
				}
			}

			$lines[$lineCount] = $line;
		}

		return $lines;
	}

	/**
	 * Retrieves a code snippet with syntax highlighting.
	 */
	public function getCodeSnippet(string $source, int $lineNumber, int $linesBefore = 2, int $linesAfter = 2): string
	{
		$offset = max($lineNumber - $linesBefore - 1, 0);

		$length = $linesAfter + $linesBefore + 1;

		$tokenLines = array_slice($this->getHighlightedLines($source), $offset, $length, true);

		$lines = $this->colorLines($tokenLines);

		return $this->lineNumbers($lines, $lineNumber);
	}

	/**
	 * Tokenizes the given source code.
	 */
	protected function getHighlightedLines(string $source): array
	{
		$source = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], $source);

		$tokens = $this->tokenize($source);

		return $this->splitToLines($tokens);
	}

	/**
	 * Adds line numbers to the given lines.
	 */
	protected function lineNumbers(array $lines, int|null $markLine = null): string
	{
		$lineLen = strlen((string) ((int) array_key_last($lines) + 1));

		$lineStrlen = $lineLen < self::WIDTH ? self::WIDTH : $lineLen;

		$snippet = '';

		$mark = '  ' . $this->arrow . ' ';

		foreach ($lines as $i => $line) {
			$coloredLineNumber = $this->coloredLineNumber(self::LINE_NUMBER, $i, $lineStrlen);

			if (! is_null($markLine)) {
				$snippet .= $markLine === $i + 1
					? $this->color->apply(self::ACTUAL_LINE_MARK, $mark)
					: self::NO_MARK;

				$coloredLineNumber = $markLine === $i + 1
					? $this->coloredLineNumber(self::MARKED_LINE_NUMBER, $i, $lineStrlen)
					: $coloredLineNumber;
			}

			$snippet .= $coloredLineNumber .
				$this->color->apply(self::LINE_NUMBER_DIVIDER, $this->delimiter) .
				$line . PHP_EOL;
		}

		return $snippet;
	}

	/**
	 * Splits tokenized code into lines.
	 */
	protected function splitToLines(array $tokens): array
	{
		$lines = [];
		$line = [];

		foreach ($tokens as $token) {
			foreach (explode("\n", $token[1]) as $count => $tokenLine) {
				if ($count > 0) {
					$lines[] = $line;
					$line = [];
				}

				if ($tokenLine === '') {
					continue;
				}

				$line[] = [$token[0], $tokenLine];
			}
		}

		$lines[] = $line;

		return $lines;
	}

	/**
	 * Highlights the specified line in the given content.
	 */
	public function highlight(string $content, int $line): string
	{
		return rtrim($this->getCodeSnippet($content, $line, 4, 4));
	}

	/**
	 * Tokenizes the source code into an array of tokens with types.
	 */
	protected function tokenize(string $source): array
	{
		$tokens = token_get_all($source);

		$output = [];
		$currentType = null;
		$buffer = '';
		$newType = null;

		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_WHITESPACE:
						break;

					case T_OPEN_TAG:
					case T_OPEN_TAG_WITH_ECHO:
					case T_CLOSE_TAG:
					case T_STRING:
					case T_VARIABLE:
						// Constants
					case T_DIR:
					case T_FILE:
					case T_METHOD_C:
					case T_DNUMBER:
					case T_LNUMBER:
					case T_NS_C:
					case T_LINE:
					case T_CLASS_C:
					case T_FUNC_C:
					case T_TRAIT_C:
						$newType = self::TOKEN_DEFAULT;
						break;

					case T_COMMENT:
					case T_DOC_COMMENT:
						$newType = self::TOKEN_COMMENT;
						break;

					case T_ENCAPSED_AND_WHITESPACE:
					case T_CONSTANT_ENCAPSED_STRING:
						$newType = self::TOKEN_STRING;
						break;

					case T_INLINE_HTML:
						$newType = self::TOKEN_HTML;
						break;

					default:
						$newType = self::TOKEN_KEYWORD;
				}
			} else {
				$newType = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
			}

			if ($currentType === null) {
				$currentType = $newType;
			}

			if ($currentType !== $newType) {
				$output[] = [$currentType, $buffer];

				$buffer = '';

				$currentType = $newType;
			}

			$buffer .= is_array($token) ? $token[1] : $token;
		}

		if (isset($newType)) {
			$output[] = [$newType, $buffer];
		}

		return $output;
	}
}
