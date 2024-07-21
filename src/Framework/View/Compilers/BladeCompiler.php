<?php

namespace MVPS\Lumis\Framework\View\Compilers;

use InvalidArgumentException;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Support\Htmlable;
use MVPS\Lumis\Framework\Contracts\View\Compiler as CompilerContract;
use MVPS\Lumis\Framework\Contracts\View\Factory as FactoryContract;
use MVPS\Lumis\Framework\Contracts\View\View;
use MVPS\Lumis\Framework\Support\Str;
use MVPS\Lumis\Framework\Support\Traits\ReflectsClosures;
use MVPS\Lumis\Framework\View\Component;

class BladeCompiler extends Compiler implements CompilerContract
{
	use Traits\CompilesClasses;
	use Traits\CompilesComments;
	use Traits\CompilesComponents;
	use Traits\CompilesConditionals;
	use Traits\CompilesEchos;
	use Traits\CompilesErrors;
	use Traits\CompilesFragments;
	use Traits\CompilesHelpers;
	use Traits\CompilesIncludes;
	use Traits\CompilesInjections;
	use Traits\CompilesJson;
	use Traits\CompilesJs;
	use Traits\CompilesLayouts;
	use Traits\CompilesLoops;
	use Traits\CompilesRawPhp;
	use Traits\CompilesStacks;
	use Traits\CompilesStyles;
	use Traits\CompilesUseStatements;
	use ReflectsClosures;

	/**
	 * The array of anonymous component namespaces to autoload from.
	 *
	 * @var array
	 */
	protected array $anonymousComponentNamespaces = [];

	/**
	 * The array of anonymous component paths to search for components in.
	 *
	 * @var array
	 */
	protected array $anonymousComponentPaths = [];

	/**
	 * The array of class component aliases and their class names.
	 *
	 * @var array
	 */
	protected array $classComponentAliases = [];

	/**
	 * The array of class component namespaces to autoload from.
	 *
	 * @var array
	 */
	protected array $classComponentNamespaces = [];

	/**
	 * All of the available compiler functions.
	 *
	 * @var string[]
	 */
	protected array $compilers = [
		// 'Comments',
		'Extensions',
		'Statements',
		'Echos',
	];

	/**
	 * Indicates if component tags should be compiled.
	 *
	 * @var bool
	 */
	protected bool $compilesComponentTags = true;

	/**
	 * All custom "condition" handlers.
	 *
	 * @var array
	 */
	protected array $conditions = [];

	/**
	 * Array of opening and closing tags for regular echos.
	 *
	 * @var string[]
	 */
	protected array $contentTags = ['{{', '}}'];

	/**
	 * All custom "directive" handlers.
	 *
	 * @var array
	 */
	protected array $customDirectives = [];

	/**
	 * The "regular" / legacy echo string format.
	 *
	 * @var string
	 */
	protected string $echoFormat = 'e(%s)';

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var string[]
	 */
	protected array $escapedTags = ['{{{', '}}}'];

	/**
	 * All of the registered extensions.
	 *
	 * @var array
	 */
	protected array $extensions = [];

	/**
	 * Array of footer lines to be added to the template.
	 *
	 * @var array
	 */
	protected array $footer = [];

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected string $path = '';

	/**
	 * All of the registered precompilers.
	 *
	 * @var array
	 */
	protected array $precompilers = [];

	/**
	 * The registered string preparation callbacks.
	 *
	 * @var array
	 */
	protected array $prepareStringsForCompilationUsing = [];

	/**
	 * Array to temporarily store the raw blocks found in the template.
	 *
	 * @var array
	 */
	protected array $rawBlocks = [];

	/**
	 * Array of opening and closing tags for raw echos.
	 *
	 * @var string[]
	 */
	protected array $rawTags = ['{!!', '!!}'];

	/**
	 * Add the stored footers onto the given content.
	 */
	protected function addFooters(string $result): string
	{
		return ltrim($result, "\n") . "\n" . implode("\n", array_reverse($this->footer));
	}

	/**
	 * Register a component alias directive.
	 */
	public function aliasComponent(string $path, string|null $alias = null): void
	{
		$alias = $alias ?: Arr::last(explode('.', $path));

		$this->directive($alias, function ($expression) use ($path) {
			return $expression
				? "<?php \$__env->startComponent('{$path}', {$expression}); ?>"
				: "<?php \$__env->startComponent('{$path}'); ?>";
		});

		$this->directive('end' . $alias, function ($expression) {
			return '<?php echo $__env->renderComponent(); ?>';
		});
	}

	/**
	 * Register an include alias directive.
	 */
	public function aliasInclude(string $path, string|null $alias = null): void
	{
		$alias = $alias ?: Arr::last(explode('.', $path));

		$this->directive($alias, function ($expression) use ($path) {
			$expression = $this->stripParentheses($expression) ?: '[]';

			return "<?php echo \$__env->make('{$path}', {$expression}, \MVPS\Lumis\Framework\Collections\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
		});
	}

	/**
	 * Register an anonymous component namespace.
	 */
	public function anonymousComponentNamespace(string $directory, string|null $prefix = null): void
	{
		$prefix ??= $directory;

		$this->anonymousComponentNamespaces[$prefix] = Str::of($directory)
			->replace('/', '.')
			->trim('. ')
			->toString();
	}

	/**
	 * Register a new anonymous component path.
	 */
	public function anonymousComponentPath(string $path, string|null $prefix = null): void
	{
		$prefixHash = md5($prefix ?: $path);

		$this->anonymousComponentPaths[] = [
			'path' => $path,
			'prefix' => $prefix,
			'prefixHash' => $prefixHash,
		];

		Container::getInstance()
			->make(FactoryContract::class)
			->addNamespace($prefixHash, $path);
	}

	/**
	 * Append the file path to the compiled string.
	 */
	protected function appendFilePath(string $contents): string
	{
		$tokens = $this->getOpenAndClosingPhpTokens($contents);

		if ($tokens->isNotEmpty() && $tokens->last() !== T_CLOSE_TAG) {
			$contents .= ' ?>';
		}

		return $contents . "<?php /**PATH {$this->getPath()} ENDPATH**/ ?>";
	}

	/**
	 * Call the given directive with the given value.
	 */
	protected function callCustomDirective(string $name, string|null $value = null): string
	{
		$value ??= '';

		if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
			$value = Str::substr($value, 1, -1);
		}

		return call_user_func($this->customDirectives[$name], trim($value));
	}

	/**
	 * Check the result of a condition.
	 */
	public function check(string $name, mixed ...$parameters): bool
	{
		return call_user_func($this->conditions[$name], ...$parameters);
	}

	/**
	 * {@inheritdoc}
	 */
	public function compile($path = null)
	{
		if ($path) {
			$this->setPath($path);
		}

		if (! is_null($this->cachePath)) {
			$contents = $this->compileString($this->files->get($this->getPath()));

			if (! empty($this->getPath())) {
				$contents = $this->appendFilePath($contents);
			}

			$this->ensureCompiledDirectoryExists(
				$compiledPath = $this->getCompiledPath($this->getPath())
			);

			$this->files->put($compiledPath, $contents);
		}
	}

	/**
	 * Compile the component tags.
	 */
	protected function compileComponentTags(string $value): string
	{
		if (! $this->compilesComponentTags) {
			return $value;
		}

		return (new ComponentTagCompiler($this->classComponentAliases, $this->classComponentNamespaces, $this))
			->compile($value);
	}

	/**
	 * Execute the user defined extensions.
	 */
	protected function compileExtensions(string $value): string
	{
		foreach ($this->extensions as $compiler) {
			$value = $compiler($value, $this);
		}

		return $value;
	}

	/**
	 * Compile a single Blade @ statement.
	 */
	protected function compileStatement(array $match): string
	{
		if (str_contains($match[1], '@')) {
			$match[0] = isset($match[3]) ? $match[1] . $match[3] : $match[1];
		} elseif (isset($this->customDirectives[$match[1]])) {
			$match[0] = $this->callCustomDirective($match[1], Arr::get($match, 3));
		} elseif (method_exists($this, $method = 'compile' . ucfirst($match[1]))) {
			$match[0] = $this->$method(Arr::get($match, 3));
		} else {
			return $match[0];
		}

		return isset($match[3]) ? $match[0] : $match[0] . $match[2];
	}

	/**
	 * Compile Blade statements that start with "@".
	 */
	protected function compileStatements(string $template): string
	{
		preg_match_all('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $template, $matches);

		$offset = 0;

		for ($i = 0; isset($matches[0][$i]); $i++) {
			$match = [
				$matches[0][$i],
				$matches[1][$i],
				$matches[2][$i],
				$matches[3][$i] ?: null,
				$matches[4][$i] ?: null,
			];

			// Check if the closing parenthesis has been correctly identified using
			// the regex pattern. If not, recursively continue to the next closing
			// parenthesis and verify again. Repeat this process until the tokenizer
			// confirms the correct closing parenthesis is found.
			while (
				isset($match[4]) &&
				Str::endsWith($match[0], ')') &&
				! $this->hasEvenNumberOfParentheses($match[0])
			) {
				$after = Str::after($template, $match[0]);

				if ($after === $template) {
					break;
				}

				$rest = Str::before($after, ')');

				if (isset($matches[0][$i + 1]) && Str::contains($rest . ')', $matches[0][$i + 1])) {
					unset($matches[0][$i + 1]);
					$i++;
				}

				$match[0] = $match[0] . $rest . ')';
				$match[3] = $match[3] . $rest . ')';
				$match[4] = $match[4] . $rest;
			}

			[$template, $offset] = $this->replaceFirstStatement(
				$match[0],
				$this->compileStatement($match),
				$template,
				$offset
			);
		}

		return $template;
	}

	/**
	 * Compile the given Blade template contents.
	 */
	public function compileString(string $value): string
	{
		[$this->footer, $result] = [[], ''];

		foreach ($this->prepareStringsForCompilationUsing as $callback) {
			$value = $callback($value);
		}

		$value = $this->storeUncompiledBlocks($value);

		// First, we will compile the Blade component tags. This precompilation step
		// transforms the component Blade tags into @component directives that Blade
		// can understand. After compiling the component tags, we should call any
		// additional precompilers to handle other preprocessing tasks.
		$value = $this->compileComponentTags($this->compileComments($value));

		foreach ($this->precompilers as $precompiler) {
			$value = $precompiler($value);
		}

		// Iterate through all tokens returned by the Zend lexer, parsing each one into
		// valid PHP. This process transforms the template into correctly rendered PHP
		// that can be executed natively.
		foreach (token_get_all($value) as $token) {
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		if (! empty($this->rawBlocks)) {
			$result = $this->restoreRawContent($result);
		}

		// Add any footer lines to the template here. These lines are typically used
		// for template inheritance, such as those added via the extends keyword.
		// Footer lines are appended at the end of the template to ensure proper
		// inheritance and template structure.
		if (count($this->footer) > 0) {
			$result = $this->addFooters($result);
		}

		if (! empty($this->echoHandlers)) {
			$result = $this->addBladeCompilerVariable($result);
		}

		return str_replace(
			['##BEGIN-COMPONENT-CLASS##', '##END-COMPONENT-CLASS##'],
			'',
			$result
		);
	}

	/**
	 * Register a class-based component alias directive.
	 */
	public function component(string $class, string|null $alias = null, string $prefix = ''): void
	{
		if (! is_null($alias) && str_contains($alias, '\\')) {
			[$class, $alias] = [$alias, $class];
		}

		if (is_null($alias)) {
			$alias = str_contains($class, '\\View\\Components\\')
				? collection(explode('\\', Str::after($class, '\\View\\Components\\')))
					->map(function ($segment) {
						return Str::kebab($segment);
					})->implode(':')
				: Str::kebab(class_basename($class));
		}

		if (! empty($prefix)) {
			$alias = $prefix . '-' . $alias;
		}

		$this->classComponentAliases[$alias] = $class;
	}

	/**
	 * Register a class-based component namespace.
	 */
	public function componentNamespace(string $namespace, string $prefix): void
	{
		$this->classComponentNamespaces[$prefix] = $namespace;
	}

	/**
	 * Register an array of class-based components.
	 */
	public function components(array $components, string $prefix = ''): void
	{
		foreach ($components as $key => $value) {
			if (is_numeric($key)) {
				$this->component($value, null, $prefix);
			} else {
				$this->component($key, $value, $prefix);
			}
		}
	}

	/**
	 * Register a handler for custom directives.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function directive(string $name, callable $handler): void
	{
		if (! preg_match('/^\w+(?:::\w+)?$/x', $name)) {
			throw new InvalidArgumentException(sprintf(
				'The directive name [%s] is not valid. Directive names must only contain alphanumeric characters and underscores.',
				$name
			));
		}

		$this->customDirectives[$name] = $handler;
	}

	/**
	 * Register a custom Blade compiler.
	 */
	public function extend(callable $compiler): void
	{
		$this->extensions[] = $compiler;
	}

	/**
	 * Get the registered anonymous component namespaces.
	 */
	public function getAnonymousComponentNamespaces(): array
	{
		return $this->anonymousComponentNamespaces;
	}

	/**
	 * Get the registered anonymous component paths.
	 */
	public function getAnonymousComponentPaths(): array
	{
		return $this->anonymousComponentPaths;
	}

	/**
	 * Get the registered class component aliases.
	 */
	public function getClassComponentAliases(): array
	{
		return $this->classComponentAliases;
	}

	/**
	 * Get the registered class component namespaces.
	 */
	public function getClassComponentNamespaces(): array
	{
		return $this->classComponentNamespaces;
	}

	/**
	 * Get the list of custom directives.
	 */
	public function getCustomDirectives(): array
	{
		return $this->customDirectives;
	}

	/**
	 * Get the extensions used by the compiler.
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

	/**
	 * Get the open and closing PHP tag tokens from the given string.
	 */
	protected function getOpenAndClosingPhpTokens(string $contents): Collection
	{
		return collection(token_get_all($contents))
			->pluck(0)
			->filter(function ($token) {
				return in_array($token, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG]);
			});
	}

	/**
	 * Get the path currently being compiled.
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get a placeholder to temporarily mark the position of raw blocks.
	 */
	protected function getRawPlaceholder(int|string $replace): string
	{
		return str_replace('#', $replace, '@__raw_block_#__@');
	}

	/**
	 * Determine if the given expression has the same number of opening and closing parentheses.
	 */
	protected function hasEvenNumberOfParentheses(string $expression): bool
	{
		$tokens = token_get_all('<?php ' . $expression);

		if (Arr::last($tokens) !== ')') {
			return false;
		}

		$opening = 0;
		$closing = 0;

		foreach ($tokens as $token) {
			if ($token == ')') {
				$closing++;
			} elseif ($token == '(') {
				$opening++;
			}
		}

		return $opening === $closing;
	}

	/**
	 * Register an "if" statement directive.
	 */
	public function if(string $name, callable $callback): void
	{
		$this->conditions[$name] = $callback;

		$this->directive($name, function ($expression) use ($name) {
			return $expression !== ''
				? "<?php if (app('blade.compiler')->check('{$name}', {$expression})): ?>"
				: "<?php if (app('blade.compiler')->check('{$name}')): ?>";
		});

		$this->directive('unless' . $name, function ($expression) use ($name) {
			return $expression !== ''
				? "<?php if (! app('blade.compiler')->check('{$name}', {$expression})): ?>"
				: "<?php if (! app('blade.compiler')->check('{$name}')): ?>";
		});

		$this->directive('else' . $name, function ($expression) use ($name) {
			return $expression !== ''
				? "<?php elseif (app('blade.compiler')->check('{$name}', {$expression})): ?>"
				: "<?php elseif (app('blade.compiler')->check('{$name}')): ?>";
		});

		$this->directive('end' . $name, function () {
			return '<?php endif; ?>';
		});
	}

	/**
	 * Register an include alias directive.
	 */
	public function include(string $path, string|null $alias = null): void
	{
		$this->aliasInclude($path, $alias);
	}

	/**
	 * Parse the tokens from the template.
	 */
	protected function parseToken(array $token): string
	{
		[$id, $content] = $token;

		if ((int) $id === T_INLINE_HTML) {
			foreach ($this->compilers as $type) {
				$content = $this->{"compile{$type}"}($content);
			}
		}

		return $content;
	}

	/**
	 * Register a new precompiler.
	 */
	public function precompiler(callable $precompiler): void
	{
		$this->precompilers[] = $precompiler;
	}

	/**
	 * Indicate that the following callable should be used to prepare strings for compilation.
	 */
	public function prepareStringsForCompilationUsing(callable $callback): static
	{
		$this->prepareStringsForCompilationUsing[] = $callback;

		return $this;
	}

	/**
	 * Evaluate and render a Blade string to HTML.
	 */
	public static function render(string $string, array $data = [], bool $deleteCachedView = false): string
	{
		$component = new class ($string) extends Component
		{
			protected $template;

			public function __construct($template)
			{
				$this->template = $template;
			}

			public function render()
			{
				return $this->template;
			}
		};

		$view = Container::getInstance()
			->make(FactoryContract::class)
			->make($component->resolveView(), $data);

		return tap($view->render(), function () use ($view, $deleteCachedView) {
			if ($deleteCachedView) {
				@unlink($view->getPath());
			}
		});
	}

	/**
	 * Render a component instance to HTML.
	 */
	public static function renderComponent(Component $component): string
	{
		$data = $component->data();

		$view = value($component->resolveView(), $data);

		if ($view instanceof View) {
			return $view->with($data)->render();
		} elseif ($view instanceof Htmlable) {
			return $view->toHtml();
		} else {
			return Container::getInstance()
				->make(FactoryContract::class)
				->make($view, $data)
				->render();
		}
	}

	/**
	 * Replace the first match for a statement compilation operation.
	 */
	protected function replaceFirstStatement(string $search, string $replace, string $subject, int $offset): array
	{
		$search = (string) $search;

		if ($search === '') {
			return $subject;
		}

		$position = strpos($subject, $search, $offset);

		if ($position !== false) {
			return [
				substr_replace($subject, $replace, $position, strlen($search)),
				$position + strlen($replace),
			];
		}

		return [$subject, 0];
	}

	/**
	 * Replace the raw placeholders with the original code stored in the raw blocks.
	 */
	protected function restoreRawContent(string $result): string
	{
		$result = preg_replace_callback('/' . $this->getRawPlaceholder('(\d+)') . '/', function ($matches) {
			return $this->rawBlocks[$matches[1]];
		}, $result);

		$this->rawBlocks = [];

		return $result;
	}

	/**
	 * Set the echo format to be used by the compiler.
	 */
	public function setEchoFormat(string $format): void
	{
		$this->echoFormat = $format;
	}

	/**
	 * Set the path currently being compiled.
	 */
	public function setPath(string $path): void
	{
		$this->path = $path;
	}

	/**
	 * Store the PHP blocks and replace them with a temporary placeholder.
	 */
	protected function storePhpBlocks(string $value): string
	{
		return preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function ($matches) {
			return $this->storeRawBlock("<?php{$matches[1]}?>");
		}, $value);
	}

	/**
	 * Store a raw block and return a unique raw placeholder.
	 */
	protected function storeRawBlock(string $value): string
	{
		return $this->getRawPlaceholder(array_push($this->rawBlocks, $value) - 1);
	}

	/**
	 * Store the blocks that do not receive compilation.
	 */
	protected function storeUncompiledBlocks(string $value): string
	{
		if (str_contains($value, '@verbatim')) {
			$value = $this->storeVerbatimBlocks($value);
		}

		if (str_contains($value, '@php')) {
			$value = $this->storePhpBlocks($value);
		}

		return $value;
	}

	/**
	 * Store the verbatim blocks and replace them with a temporary placeholder.
	 */
	protected function storeVerbatimBlocks(string $value): string
	{
		return preg_replace_callback('/(?<!@)@verbatim(\s*)(.*?)@endverbatim/s', function ($matches) {
			return $matches[1] . $this->storeRawBlock($matches[2]);
		}, $value);
	}

	/**
	 * Strip the parentheses from the given expression.
	 */
	public function stripParentheses(string $expression): string
	{
		if (Str::startsWith($expression, '(')) {
			$expression = substr($expression, 1, -1);
		}

		return $expression;
	}

	/**
	 * Set the "echo" format to double encode entities.
	 */
	public function withDoubleEncoding(): void
	{
		$this->setEchoFormat('e(%s, true)');
	}

	/**
	 * Indicate that component tags should not be compiled.
	 */
	public function withoutComponentTags(): void
	{
		$this->compilesComponentTags = false;
	}

	/**
	 * Set the "echo" format to not double encode entities.
	 */
	public function withoutDoubleEncoding(): void
	{
		$this->setEchoFormat('e(%s, false)');
	}
}
