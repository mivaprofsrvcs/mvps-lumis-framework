<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use Closure;
use MVPS\Lumis\Framework\Collections\Arr;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator;
use MVPS\Lumis\Framework\Routing\Route;
use MVPS\Lumis\Framework\Routing\Router;
use MVPS\Lumis\Framework\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'route:list')]
class RouteListCommand extends Command
{
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $description = 'List all registered routes';

	/**
	 * The table headers for the command.
	 *
	 * @var string[]
	 */
	protected array $headers = [
		'Domain',
		'Method',
		'URI',
		'Name',
		'Action',
		// 'Middleware',
	];

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $name = 'route:list';

	/**
	 * The router instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\Router
	 */
	protected Router $router;

	/**
	 * The terminal width resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected static Closure|null $terminalWidthResolver = null;

	/**
	 * The verb colors for the command.
	 *
	 * @var array
	 */
	protected array $verbColors = [
		'ANY' => 'red',
		'GET' => 'blue',
		'HEAD' => '#6C7280',
		'OPTIONS' => '#6C7280',
		'POST' => 'yellow',
		'PUT' => 'yellow',
		'PATCH' => 'yellow',
		'DELETE' => 'red',
	];

	/**
	 * Create a new route command instance.
	 */
	public function __construct(Router $router)
	{
		parent::__construct();

		$this->router = $router;
	}

	/**
	 * Convert the given routes to JSON.
	 */
	protected function asJson(Collection $routes): string
	{
		return $routes
			->map(function ($route) {
				$route['middleware'] = empty($route['middleware']) ? [] : explode("\n", $route['middleware']);

				return $route;
			})
			->values()
			->toJson();
	}

	/**
	 * Determine and return the output for displaying the number of routes in the CLI output.
	 */
	protected function determineRouteCountOutput(Collection $routes, int $terminalWidth): string
	{
		$routesCount = $routes->count();
		$routeCountText = sprintf('Showing [%s] routes', (string) $routesCount);

		$offset = $terminalWidth - mb_strlen($routeCountText) - 2;

		$spaces = str_repeat(' ', $offset);

		return $spaces . sprintf('<fg=blue;options=bold>Showing [%s] routes</>', (string) $routesCount);
	}

	/**
	 * Display the route information on the console.
	 */
	protected function displayRoutes(array $routes): void
	{
		$routes = collection($routes);

		$this->output->writeln(
			$this->option('json') ? $this->asJson($routes) : $this->forCli($routes)
		);
	}

	/**
	 * Filter the route by URI and / or name.
	 */
	protected function filterRoute(array $route): array|null
	{
		if (
			($this->option('name') && ! Str::contains((string) $route['name'], $this->option('name'))) ||
			($this->option('path') && ! Str::contains($route['uri'], $this->option('path'))) ||
			($this->option('method') && ! Str::contains($route['method'], strtoupper($this->option('method')))) ||
			($this->option('domain') && ! Str::contains((string) $route['domain'], $this->option('domain'))) ||
			($this->option('except-vendor') && $route['vendor']) ||
			($this->option('only-vendor') && ! $route['vendor'])
		) {
			return null;
		}

		if ($this->option('except-path')) {
			foreach (explode(',', $this->option('except-path')) as $path) {
				if (str_contains($route['uri'], $path)) {
					return null;
				}
			}
		}

		return $route;
	}

	/**
	 * Convert the given routes to regular CLI output.
	 */
	protected function forCli(Collection $routes): array
	{
		$routes = $routes->map(
			fn ($route) => array_merge($route, [
				'action' => $this->formatActionForCli($route),
				'method' => $route['method'] === 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS' ? 'ANY' : $route['method'],
				'uri' => $route['domain'] ? $route['domain'] . '/' . ltrim($route['uri'], '/') : $route['uri'],
			]),
		);

		$maxMethod = mb_strlen($routes->max('method'));

		$terminalWidth = $this->getTerminalWidth();

		$routeCount = $this->determineRouteCountOutput($routes, $terminalWidth);

		return $routes->map(function ($route) use ($maxMethod, $terminalWidth) {
			[
				'action' => $action,
				'domain' => $domain,
				'method' => $method,
				// 'middleware' => $middleware,
				'uri' => $uri,
			] = $route;

			// $middleware = Str::of($middleware)
			// 	->explode("\n")
			// 	->filter()
			// 	->whenNotEmpty(
			// 		fn ($collection) => $collection->map(
			// 			fn ($middleware) => sprintf('         %s⇂ %s', str_repeat(' ', $maxMethod), $middleware)
			// 		)
			// 	)
			// 	->implode("\n");

			$spaces = str_repeat(' ', max($maxMethod + 6 - mb_strlen($method), 0));

			$dots = str_repeat(
				'.',
				max($terminalWidth - mb_strlen($method . $spaces . $uri . $action) - 6 - ($action ? 1 : 0), 0)
			);

			$dots = empty($dots) ? $dots : " $dots";

			if (
				$action &&
				! $this->output->isVerbose() &&
				mb_strlen($method . $spaces . $uri . $action . $dots) > ($terminalWidth - 6)
			) {
				$action = substr($action, 0, $terminalWidth - 7 - mb_strlen($method . $spaces . $uri . $dots)) . '…';
			}

			$method = Str::of($method)
				->explode('|')
				->map(
					fn ($method) => sprintf('<fg=%s>%s</>', $this->verbColors[$method] ?? 'default', $method),
				)
				->implode('<fg=#6C7280>|</>');

			return [sprintf(
				'  <fg=white;options=bold>%s</> %s<fg=white>%s</><fg=#6C7280>%s %s</>',
				$method,
				$spaces,
				preg_replace('#({[^}]+})#', '<fg=yellow>$1</>', $uri),
				$dots,
				str_replace('   ', ' › ', $action ?? ''),
			), $this->output->isVerbose() && ! empty($middleware) ? "<fg=#6C7280>$middleware</>" : null];
		})
			->flatten()
			->filter()
			->prepend('')
			->push('')->push($routeCount)->push('')
			->toArray();
	}

	/**
	 * Get the formatted action for display on the CLI.
	 */
	protected function formatActionForCli(array $route): string
	{
		['action' => $action, 'name' => $name] = $route;

		// TODO: Update conditional when implementing View support
		// if ($action === 'Closure' || $action === ViewController::class) {
		if ($action === 'Closure') {
			return (string) $name;
		}

		$name = $name ? "$name   " : '';

		$rootControllerNamespace = $this->lumis[UrlGenerator::class]->getRootControllerNamespace();

		if ($rootControllerNamespace === '') {
			$rootControllerNamespace = $this->lumis->getNamespace() . 'Http\\Controllers';
		}

		if (str_starts_with($action, $rootControllerNamespace)) {
			return $name . substr($action, mb_strlen($rootControllerNamespace) + 1);
		}

		$actionClass = explode('@', $action)[0];

		if (
			class_exists($actionClass) &&
			str_starts_with((new ReflectionClass($actionClass))->getFilename(), base_path('vendor'))
		) {
			$actionCollection = collection(explode('\\', $action));

			return $name . $actionCollection->take(2)->implode('\\') . '   ' . $actionCollection->last();
		}

		return $name . $action;
	}

	/**
	 * Get the column names to show (lowercase table headers).
	 */
	protected function getColumns(): array
	{
		return array_map('strtolower', $this->headers);
	}

	/**
	 * Get the table headers for the visible columns.
	 */
	protected function getHeaders(): array
	{
		return Arr::only($this->headers, array_keys($this->getColumns()));
	}

	/**
	 * Get the middleware for the route.
	 *
	 * TODO: Implement this when adding middleware functionality
	 */
	// protected function getMiddleware(Route $route): string
	// {
	// 	return collection($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
	// 		return $middleware instanceof Closure ? 'Closure' : $middleware;
	// 	})->implode("\n");
	// }

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'json',
				null,
				InputOption::VALUE_NONE,
				'Output the route list as JSON',
			],
			[
				'method',
				null,
				InputOption::VALUE_OPTIONAL,
				'Filter the routes by method',
			],
			[
				'name',
				null,
				InputOption::VALUE_OPTIONAL,
				'Filter the routes by name',
			],
			[
				'domain',
				null,
				InputOption::VALUE_OPTIONAL,
				'Filter the routes by domain',
			],
			[
				'path',
				null,
				InputOption::VALUE_OPTIONAL,
				'Only show routes matching the given path pattern',
			],
			[
				'except-path',
				null,
				InputOption::VALUE_OPTIONAL,
				'Do not display the routes matching the given path pattern',
			],
			[
				'reverse',
				'r',
				InputOption::VALUE_NONE,
				'Reverse the ordering of the routes',
			],
			[
				'sort',
				null,
				InputOption::VALUE_OPTIONAL,
				'The column (domain, method, uri, name, action, middleware) to sort by', 'uri',
			],
			[
				'except-vendor',
				null,
				InputOption::VALUE_NONE,
				'Do not display routes defined by vendor packages',
			],
			[
				'only-vendor',
				null,
				InputOption::VALUE_NONE,
				'Only display routes defined by vendor packages',
			],
		];
	}

	/**
	 * Get the route information for a given route.
	 */
	protected function getRouteInformation(Route $route): array|null
	{
		return $this->filterRoute([
			'domain' => $route->domain(),
			'method' => implode('|', $route->methods()),
			'uri' => $route->uri(),
			'name' => $route->getName(),
			'action' => ltrim($route->getActionName(), '\\'),
			// 'middleware' => $this->getMiddleware($route),
			// 'vendor' => $this->isVendorRoute($route),
		]);
	}

	/**
	 * Compile the routes into a displayable format.
	 */
	protected function getRoutes(): array
	{
		$routes = collection($this->router->getRoutes())
			->map(function ($route) {
				return $this->getRouteInformation($route);
			})
			->filter()
			->all();

		$sort = $this->option('sort');

		if (! is_null($sort)) {
			$routes = $this->sortRoutes($sort, $routes);
		} else {
			$routes = $this->sortRoutes('uri', $routes);
		}

		if ($this->option('reverse')) {
			$routes = array_reverse($routes);
		}

		return $this->pluckColumns($routes);
	}

	/**
	 * Get the terminal width.
	 */
	public static function getTerminalWidth(): int
	{
		return is_null(static::$terminalWidthResolver)
			? (new Terminal)->getWidth()
			: call_user_func(static::$terminalWidthResolver);
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): mixed
	{
		// TODO: Implement when adding middleware functionality
		// if (! $this->output->isVeryVerbose()) {
		// 	$this->router->flushMiddlewareGroups();
		// }

		if (! $this->router->getRoutes()->count()) {
			return $this->components->error('Your application currently has no defined routes.');
		}

		$routes = $this->getRoutes();

		if (empty($routes)) {
			return $this->components->error('No routes in your application match the specified criteria.');
		}

		$this->displayRoutes($routes);

		return true;
	}

	/**
	 * Determine if the route uses a framework controller.
	 */
	protected function isFrameworkController(Route $route): bool
	{
		return in_array($route->getControllerClass(), [
			'\MVPS\Lumis\Framework\Routing\RedirectController',
			'\MVPS\Lumis\Framework\Routing\ViewController',
		], true);
	}

	/**
	 * Determine if the route has been defined outside of the application.
	 */
	protected function isVendorRoute(Route $route): bool
	{
		if ($route->action['uses'] instanceof Closure) {
			$path = (new ReflectionFunction($route->action['uses']))
				->getFileName();
		} elseif (is_string($route->action['uses']) && str_contains($route->action['uses'], 'SerializableClosure')) {
			return false;
		} elseif (is_string($route->action['uses'])) {
			if ($this->isFrameworkController($route)) {
				return false;
			}

			$path = (new ReflectionClass($route->getControllerClass()))
				->getFileName();
		} else {
			return false;
		}

		return str_starts_with($path, base_path('vendor'));
	}

	/**
	 * Parse the column list.
	 */
	protected function parseColumns(array $columns): array
	{
		$results = [];

		foreach ($columns as $column) {
			if (str_contains($column, ',')) {
				$results = array_merge($results, explode(',', $column));
			} else {
				$results[] = $column;
			}
		}

		return array_map('strtolower', $results);
	}

	/**
	 * Remove unnecessary columns from the routes.
	 */
	protected function pluckColumns(array $routes): array
	{
		return array_map(function ($route) {
			return Arr::only($route, $this->getColumns());
		}, $routes);
	}

	/**
	 * Set a callback that should be used when resolving the terminal width.
	 */
	public static function resolveTerminalWidthUsing(Closure|null $resolver): void
	{
		static::$terminalWidthResolver = $resolver;
	}

	/**
	 * Sort the routes by a given element.
	 */
	protected function sortRoutes(string $sort, array $routes): array
	{
		if (Str::contains($sort, ',')) {
			$sort = explode(',', $sort);
		}

		return collection($routes)
			->sortBy($sort)
			->toArray();
	}
}
