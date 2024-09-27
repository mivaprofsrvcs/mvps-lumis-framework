<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use Closure;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Support\Composer;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'about')]
class AboutCommand extends Command
{
	/**
	 * The Composer instance.
	 *
	 * @var \MVPS\Lumis\Framework\Support\Composer
	 */
	protected Composer $composer;

	/**
	 * The registered callables that add custom data to the command output.
	 *
	 * @var array
	 */
	protected static array $customDataResolvers = [];

	/**
	 * The data to display.
	 *
	 * @var array
	 */
	protected static array $data = [];

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Display basic information about your application';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'about
		{--only= : The section to display}
		{--json : Output the information as JSON}';

	/**
	 * Create a new about command instance.
	 */
	public function __construct(Composer $composer)
	{
		parent::__construct();

		$this->composer = $composer;
	}

	/**
	 * Add additional data to the output of the "about" command.
	 */
	public static function add(string $section, callable|string|array $data, string|null $value = null): void
	{
		static::$customDataResolvers[] = fn () => static::addToSection($section, $data, $value);
	}

	/**
	 * Add additional data to the output of the "about" command.
	 */
	protected static function addToSection(
		string $section,
		callable|string|array $data,
		string|null $value = null
	): void {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				self::$data[$section][] = [$key, $value];
			}
		} elseif (is_callable($data) || (is_null($value) && class_exists($data))) {
			self::$data[$section][] = $data;
		} else {
			self::$data[$section][] = [$data, $value];
		}
	}

	/**
	 * Display the application information.
	 */
	protected function display(Collection $data): void
	{
		$this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
	}

	/**
	 * Display the application information as a detail view.
	 */
	protected function displayDetail(Collection $data): void
	{
		$data->each(function ($data, $section) {
			$this->newLine();

			$this->components->twoColumnDetail('  <fg=green;options=bold>' . $section . '</>');

			$data->pipe(fn ($data) => $section !== 'Environment' ? $data->sort() : $data)
				->each(function ($detail) {
					[$label, $value] = $detail;

					$this->components->twoColumnDetail($label, value($value, false));
				});
		});
	}

	/**
	 * Display the application information as JSON.
	 */
	protected function displayJson(Collection $data): void
	{
		$output = $data->flatMap(function ($data, $section) {
			return [
				(string) Str::of($section)->snake() => $data->mapWithKeys(fn ($item, $key) => [
					$this->toSearchKeyword($item[0]) => value($item[1], true),
				]),
			];
		});

		$this->output->writeln(strip_tags(json_encode($output)));
	}

	/**
	 * Flush the registered about data.
	 */
	public static function flushState(): void
	{
		static::$data = [];

		static::$customDataResolvers = [];
	}

	/**
	 * Materialize a function that formats a given value for CLI or JSON output.
	 */
	public static function format(mixed $value, Closure|null $console = null, Closure|null $json = null): Closure
	{
		return function ($isJson) use ($value, $console, $json) {
			if ($isJson === true && $json instanceof Closure) {
				return value($json, $value);
			} elseif ($isJson === false && $console instanceof Closure) {
				return value($console, $value);
			}

			return value($value);
		};
	}

	/**
	 * Gather information about the application.
	 */
	protected function gatherApplicationInformation(): void
	{
		self::$data = [];

		$formatEnabledStatus = fn ($value) => $value
			? '<fg=yellow;options=bold>ENABLED</>'
			: 'OFF';

		$formatCachedStatus = fn ($value) => $value
			? '<fg=green;options=bold>CACHED</>'
			: '<fg=yellow;options=bold>NOT CACHED</>';

		static::addToSection('Environment', fn () => [
			'Application Name' => config('app.name'),
			'Lumis Version' => $this->lumis->version(),
			'PHP Version' => phpversion(),
			'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
			'Environment' => $this->lumis->environment(),
			'Debug Mode' => static::format(config('app.debug'), console: $formatEnabledStatus),
			'URL' => Str::of(config('app.url'))->replace(['http://', 'https://'], ''),
			'Timezone' => config('app.timezone'),
		]);

		// TODO: Implement this when adding caching support
		// static::addToSection('Cache', fn () => [
		// 	'Config' => static::format($this->lumis->configurationIsCached(), console: $formatCachedStatus),
		// 	'Events' => static::format($this->lumis->eventsAreCached(), console: $formatCachedStatus),
		// 	'Routes' => static::format($this->lumis->routesAreCached(), console: $formatCachedStatus),
		// 	'Views' => static::format(
		// 		$this->hasPhpFiles($this->lumis->storagePath('framework/views')), console: $formatCachedStatus
		// 	),
		// ]);

		// TODO: Implement this when adding caching support
		static::addToSection('Drivers', fn () => array_filter([
			'Cache' => config('cache.default'),
			'Database' => config('database.default'),
			'Log Level' => config('logging.level'),
		]));

		collection(static::$customDataResolvers)
			->each
			->__invoke();
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		$this->gatherApplicationInformation();

		collection(static::$data)
			->map(
				fn ($items) => collection($items)
					->map(function ($value) {
						if (is_array($value)) {
							return [$value];
						}

						if (is_string($value)) {
							$value = $this->lumis->make($value);
						}

						return collection($this->lumis->call($value))
							->map(fn ($value, $key) => [$key, $value])
							->values()
							->all();
					})
					->flatten(1)
			)
			->sortBy(function ($data, $key) {
				$index = array_search($key, ['Environment']);

				return $index === false ? 99 : $index;
			})
			->filter(function ($data, $key) {
				return $this->option('only') ? in_array($this->toSearchKeyword($key), $this->sections()) : true;
			})
			->pipe(fn ($data) => $this->display($data));

		$this->newLine();

		return 0;
	}

	/**
	 * Determine whether the given directory has PHP files.
	 */
	protected function hasPhpFiles(string $path): bool
	{
		return count(glob($path . '/*.php')) > 0;
	}

	/**
	 * Get the sections provided to the command.
	 */
	protected function sections(): array
	{
		return collection(explode(',', $this->option('only') ?? ''))
			->filter()
			->map(fn ($only) => $this->toSearchKeyword($only))
			->all();
	}

	/**
	 * Format the given string for searching.
	 */
	protected function toSearchKeyword(string $value): string
	{
		return (string) Str::of($value)
			->lower()
			->snake();
	}
}
