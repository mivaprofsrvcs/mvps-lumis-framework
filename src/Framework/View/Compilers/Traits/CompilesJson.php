<?php

namespace MVPS\Lumis\Framework\View\Compilers\Traits;

trait CompilesJson
{
	/**
	 * The default JSON encoding options.
	 *
	 * @var int
	 */
	private int $encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

	/**
	 * Compile the JSON statement into valid PHP.
	 */
	protected function compileJson(string|null $expression = null): string
	{
		$parts = explode(',', $this->stripParentheses($expression ?? ''));

		$options = isset($parts[1]) ? trim($parts[1]) : $this->encodingOptions;

		$depth = isset($parts[2]) ? trim($parts[2]) : 512;

		return "<?php echo json_encode($parts[0], $options, $depth) ?>";
	}
}
