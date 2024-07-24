<?php

namespace MVPS\Lumis\Framework\Routing;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use MVPS\Lumis\Framework\Contracts\Routing\UrlRoutable;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Routing\Exceptions\RouteNotFoundException;
use MVPS\Lumis\Framework\Support\Arr;
use MVPS\Lumis\Framework\Support\Str;

class UrlGenerator implements UrlGeneratorContract
{
	use InteractsWithTime;
	use Macroable;

	/**
	 * The asset root URL.
	 *
	 * @var string
	 */
	protected string $assetRoot;

	/**
	 * A cached copy of the URL root for the current request.
	 *
	 * @var string|null
	 */
	protected string|null $cachedRoot;

	/**
	 * A cached copy of the URL scheme for the current request.
	 *
	 * @var string|null
	 */
	protected string|null $cachedScheme;

	/**
	 * The forced URL root.
	 *
	 * @var string
	 */
	protected string $forcedRoot = '';

	/**
	 * The forced scheme for URLs.
	 *
	 * @var string
	 */
	protected $forceScheme = '';

	/**
	 * The callback to use to format hosts.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $formatHostUsing = null;

	/**
	 * The callback to use to format paths.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $formatPathUsing = null;

	/**
	 * The encryption key resolver callable.
	 *
	 * @var callable
	 */
	protected $keyResolver;

	/**
	 * The missing named route resolver callable.
	 *
	 * @var callable
	 */
	protected $missingNamedRouteResolver;

	/**
	 * The request instance.
	 *
	 * @var \MVPS\Lumis\Framework\Http\Request
	 */
	protected Request $request;

	/**
	 * The root namespace being applied to controller actions.
	 *
	 * @var string
	 */
	protected string $rootNamespace = '';

	/**
	 * The route collection.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\RouteCollection
	 */
	protected RouteCollection $routes;

	/**
	 * The route URL generator instance.
	 *
	 * @var \MVPS\Lumis\Framework\Routing\RouteUrlGenerator|null
	 */
	protected RouteUrlGenerator|null $routeGenerator = null;

	/**
	 * The session resolver callable.
	 *
	 * @var callable
	 */
	protected $sessionResolver;

	/**
	 * Create a new URL Generator instance.
	 */
	public function __construct(RouteCollection $routes, Request $request, string|null $assetRoot = null)
	{
		$this->routes = $routes;
		$this->assetRoot = $assetRoot;

		$this->setRequest($request);
	}

	/**
	 * Get the URL to a controller action.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function action(string|array $action, mixed $parameters = [], bool $absolute = true): string
	{
		$formattedAction = $this->formatAction($action);
		$route = $this->routes->getByAction($formattedAction);

		if (is_null($route)) {
			throw new InvalidArgumentException("Action {$formattedAction} not defined.");
		}

		return $this->toRoute($route, $parameters, $absolute);
	}

	/**
	 * Generate the URL to an application asset.
	 */
	public function asset(string $path, bool|null $secure = null): string
	{
		if ($this->isValidUrl($path)) {
			return $path;
		}

		// After retrieving the root URL, check if it contains an index.php file.
		// If found, remove it as it is only needed for routing to application
		// endpoints, not for asset paths.
		$root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));

		return Str::finish($this->removeIndex($root), '/') . trim($path, '/');
	}

	/**
	 * Generate the URL to an asset from a custom root domain such as CDN, etc.
	 */
	public function assetFrom(string $root, string $path, bool|null $secure = null): string
	{
		// After obtaining the root URL, check if it contains an index.php file
		// in the paths. If present, remove it as it is unnecessary for asset
		// paths and only required for routing to application endpoints.
		$root = $this->formatRoot($this->formatScheme($secure), $root);

		return $this->removeIndex($root) . '/' . trim($path, '/');
	}

	/**
	 * Get the current URL for the request.
	 */
	public function current(): string
	{
		return $this->to($this->request->getPath());
	}

	/**
	 * Set the default named parameters used by the URL generator.
	 */
	public function defaults(array $defaults): void
	{
		$this->routeUrl()->defaults($defaults);
	}

	/**
	 * Ensure the given signed route parameters are not reserved.
	 */
	protected function ensureSignedRouteParametersAreNotReserved(mixed $parameters): void
	{
		if (array_key_exists('signature', $parameters)) {
			throw new InvalidArgumentException(
				'"Signature" is a reserved parameter when generating signed routes. Please rename your route parameter.'
			);
		}

		if (array_key_exists('expires', $parameters)) {
			throw new InvalidArgumentException(
				'"Expires" is a reserved parameter when generating signed routes. Please rename your route parameter.'
			);
		}
	}

	/**
	 * Extract the query string from the given path.
	 */
	protected function extractQueryString(string $path): array
	{
		$queryPosition = strpos($path, '?');

		if ($queryPosition !== false) {
			return [
				substr($path, 0, $queryPosition),
				substr($path, $queryPosition),
			];
		}

		return [$path, ''];
	}

	/**
	 * Set the forced root URL.
	 */
	public function forceRootUrl(string|null $root): void
	{
		$this->forcedRoot = $root ? rtrim($root, '/') : null;

		$this->cachedRoot = null;
	}

	/**
	 * Force the scheme for URLs.
	 */
	public function forceScheme(string|null $scheme): void
	{
		$this->cachedScheme = null;

		$this->forceScheme = $scheme ? $scheme . '://' : null;
	}

	/**
	 * Format the given URL segments into a single URL.
	 */
	public function format(string $root, string $path, Route|null $route = null): string
	{
		$path = '/' . trim($path, '/');

		if ($this->formatHostUsing) {
			$root = call_user_func($this->formatHostUsing, $root, $route);
		}

		if ($this->formatPathUsing) {
			$path = call_user_func($this->formatPathUsing, $path, $route);
		}

		return trim($root . $path, '/');
	}

	/**
	 * Format the given controller action.
	 */
	protected function formatAction(string|array $action): string
	{
		if (is_array($action)) {
			$action = '\\' . implode('@', $action);
		}

		if ($this->rootNamespace && ! str_starts_with($action, '\\')) {
			return $this->rootNamespace . '\\' . $action;
		}

		return trim($action, '\\');
	}

	/**
	 * Set a callback to be used to format the host of generated URLs.
	 */
	public function formatHostUsing(Closure $callback): static
	{
		$this->formatHostUsing = $callback;

		return $this;
	}

	/**
	 * Format the array of URL parameters.
	 */
	public function formatParameters(mixed $parameters): array
	{
		$parameters = Arr::wrap($parameters);

		foreach ($parameters as $key => $parameter) {
			if ($parameter instanceof UrlRoutable) {
				$parameters[$key] = $parameter->getRouteKey();
			}
		}

		return $parameters;
	}

	/**
	 * Set a callback to be used to format the path of generated URLs.
	 */
	public function formatPathUsing(Closure $callback): static
	{
		$this->formatPathUsing = $callback;

		return $this;
	}

	/**
	 * Get the base URL for the request.
	 */
	public function formatRoot(string $scheme, string|null $root = null): string
	{
		if (is_null($root)) {
			if (is_null($this->cachedRoot)) {
				$this->cachedRoot = $this->forcedRoot ?: $this->request->getRoot();
			}

			$root = $this->cachedRoot;
		}

		$start = str_starts_with($root, 'http://') ? 'http://' : 'https://';

		return preg_replace('~' . $start . '~', $scheme, $root, 1);
	}

	/**
	 * Get the default scheme for a raw URL.
	 */
	public function formatScheme(bool|null $secure = null): string
	{
		if (! is_null($secure)) {
			return $secure ? 'https://' : 'http://';
		}

		if (is_null($this->cachedScheme)) {
			$this->cachedScheme = $this->forceScheme ?: $this->request->getScheme() . '://';
		}

		return $this->cachedScheme;
	}

	/**
	 * Get the full URL for the current request.
	 */
	public function full(): string
	{
		return $this->request->getFullUrl();
	}

	/**
	 * Get the default named parameters used by the URL generator.
	 */
	public function getDefaultParameters(): array
	{
		return $this->routeUrl()->defaultParameters;
	}

	/**
	 * Get the request instance.
	 */
	public function getRequest(): Request
	{
		return $this->request;
	}

	/**
	 * Get the root controller namespace.
	 */
	public function getRootControllerNamespace(): string
	{
		return $this->rootNamespace;
	}

	/**
	 * Determine if the signature from the given request matches the URL.
	 */
	public function hasCorrectSignature(Request $request, bool $absolute = true, array $ignoreQuery = []): bool
	{
		$ignoreQuery[] = 'signature';

		$url = $absolute ? $request->getUrl() : $request->getPath();

		$queryString = collection(explode('&', $request->queryString()))
			->reject(fn ($parameter) => in_array(Str::before($parameter, '='), $ignoreQuery))
			->join('&');

		$original = rtrim($url . '?' . $queryString, '?');

		$keys = call_user_func($this->keyResolver);

		$keys = is_array($keys) ? $keys : [$keys];

		foreach ($keys as $key) {
			if (hash_equals(hash_hmac('sha256', $original, $key), (string) $request->query('signature', ''))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the given request has a valid signature for a relative URL.
	 */
	public function hasValidRelativeSignature(Request $request, array $ignoreQuery = []): bool
	{
		return $this->hasValidSignature($request, false, $ignoreQuery);
	}

	/**
	 * Determine if the given request has a valid signature.
	 */
	public function hasValidSignature(Request $request, bool $absolute = true, array $ignoreQuery = []): bool
	{
		return $this->hasCorrectSignature($request, $absolute, $ignoreQuery) && $this->signatureHasNotExpired($request);
	}

	/**
	 * Determine if the given path is a valid URL.
	 */
	public function isValidUrl(string $path): bool
	{
		if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
			return filter_var($path, FILTER_VALIDATE_URL) !== false;
		}

		return true;
	}

	/**
	 * Get the path formatter being used by the URL generator.
	 */
	public function pathFormatter(): Closure
	{
		return $this->formatPathUsing ?: function ($path) {
			return $path;
		};
	}

	/**
	 * Get the previous path info for the request.
	 */
	public function previousPath(mixed $fallback = false): string
	{
		$previousPath = str_replace(
			$this->to('/'),
			'',
			rtrim(preg_replace('/\?.*/', '', $this->previous($fallback)), '/')
		);

		return $previousPath === '' ? '/' : $previousPath;
	}

	/**
	 * Generate an absolute URL with the given query parameters.
	 */
	public function query(string $path, array $query = [], mixed $extra = [], bool|null $secure = null): string
	{
		[$path, $existingQueryString] = $this->extractQueryString($path);

		parse_str(Str::after($existingQueryString, '?'), $existingQueryArray);

		return rtrim(
			$this->to($path . '?' . Arr::query(array_merge($existingQueryArray, $query)), $extra, $secure),
			'?'
		);
	}

	/**
	 * Remove the index.php file from a path.
	 */
	protected function removeIndex(string $root): string
	{
		$index = 'index.php';

		return str_contains($root, $index) ? str_replace('/' . $index, '', $root) : $root;
	}

	/**
	 * Set the callback that should be used to attempt to resolve missing named routes.
	 */
	public function resolveMissingNamedRoutesUsing(callable $missingNamedRouteResolver): static
	{
		$this->missingNamedRouteResolver = $missingNamedRouteResolver;

		return $this;
	}

	/**
	 * Get the URL to a named route.
	 *
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\RouteNotFoundException
	 */
	public function route(string $name, mixed $parameters = [], bool $absolute = true): string
	{
		$route = $this->routes->getByName($name);

		if (! is_null($route)) {
			return $this->toRoute($route, $parameters, $absolute);
		}

		if (
			! is_null($this->missingNamedRouteResolver) &&
			! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))
		) {
			return $url;
		}

		throw new RouteNotFoundException("Route [{$name}] not defined.");
	}

	/**
	 * Get the Route URL generator instance.
	 */
	protected function routeUrl(): RouteUrlGenerator
	{
		if (is_null($this->routeGenerator)) {
			$this->routeGenerator = new RouteUrlGenerator($this, $this->request);
		}

		return $this->routeGenerator;
	}

	/**
	 * Generate a secure, absolute URL to the given path.
	 */
	public function secure(string $path, array $parameters = []): string
	{
		return $this->to($path, $parameters, true);
	}

	/**
	 * Generate the URL to a secure asset.
	 */
	public function secureAsset(string $path): string
	{
		return $this->asset($path, true);
	}

	/**
	 * Set the encryption key resolver.
	 */
	public function setKeyResolver(callable $keyResolver): static
	{
		$this->keyResolver = $keyResolver;

		return $this;
	}

	/**
	 * Set the current request instance.
	 */
	public function setRequest(Request $request): static
	{
		$this->request = $request;

		$this->cachedRoot = null;
		$this->cachedScheme = null;

		tap(optional($this->routeGenerator)->defaultParameters ?: [], function ($defaults) {
			$this->routeGenerator = null;

			if (! empty($defaults)) {
				$this->defaults($defaults);
			}
		});

		return $this;
	}

	/**
	 * Set the root controller namespace.
	 */
	public function setRootControllerNamespace(string $rootNamespace): static
	{
		$this->rootNamespace = $rootNamespace;

		return $this;
	}

	/**
	 * Set the route collection.
	 */
	public function setRoutes(RouteCollection $routes): static
	{
		$this->routes = $routes;

		return $this;
	}

	/**
	 * Determine if the expires timestamp from the given request is not from the past.
	 */
	public function signatureHasNotExpired(Request $request): bool
	{
		$expires = $request->query('expires');

		return ! ($expires && Carbon::now()->getTimestamp() > $expires);
	}

	/**
	 * Create a signed route URL for a named route.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function signedRoute(
		string $name,
		mixed $parameters = [],
		DateTimeInterface|DateInterval|int|null $expiration = null,
		bool $absolute = true
	): string {
		$this->ensureSignedRouteParametersAreNotReserved($parameters = Arr::wrap($parameters));

		if ($expiration) {
			$parameters = $parameters + ['expires' => $this->availableAt($expiration)];
		}

		ksort($parameters);

		$key = call_user_func($this->keyResolver);

		return $this->route($name, $parameters + [
			'signature' => hash_hmac(
				'sha256',
				$this->route($name, $parameters, $absolute),
				is_array($key) ? $key[0] : $key
			),
		], $absolute);
	}

	/**
	 * Create a temporary signed route URL for a named route.
	 */
	public function temporarySignedRoute(
		string $name,
		DateTimeInterface|DateInterval|int $expiration,
		array $parameters = [],
		bool $absolute = true
	): string {
		return $this->signedRoute($name, $parameters, $expiration, $absolute);
	}

	/**
	 * Generate an absolute URL to the given path.
	 */
	public function to(string $path, mixed $extra = [], bool|null $secure = null): string
	{
		// First, we check if the URL is already valid. If it is, we simply
		// return it as is. This approach saves developers from needing to
		// validate the URL beforehand.
		if ($this->isValidUrl($path)) {
			return $path;
		}

		$tail = implode('/', array_map('rawurlencode', (array) $this->formatParameters($extra)));

		// After obtaining the scheme, we will compile the "tail" by concatenating
		// the values into a single string, delimited by slashes. This makes it
		// easier to pass the array of parameters to the URL as a sequence of segments.
		$root = $this->formatRoot($this->formatScheme($secure));

		[$path, $query] = $this->extractQueryString($path);

		return $this->format($root, '/' . trim($path . '/' . $tail, '/')) . $query;
	}

	/**
	 * Get the URL for a given route instance.
	 *
	 * @throws \MVPS\Lumis\Framework\Routing\Exceptions\UrlGenerationException
	 */
	public function toRoute(Route $route, mixed $parameters, bool $absolute): string
	{
		$parameters = collection(Arr::wrap($parameters))
			->map(function ($value, $key) use ($route) {
				return $value instanceof UrlRoutable && $route->bindingFieldFor($key)
					? $value->{$route->bindingFieldFor($key)}
					: $value;
			})
			->all();

		array_walk_recursive($parameters, function (&$item) {
			if ($item instanceof BackedEnum) {
				$item = $item->value;
			}
		});

		return $this->routeUrl()->to($route, $this->formatParameters($parameters), $absolute);
	}

	/**
	 * Clone a new instance of the URL generator with a different encryption key resolver.
	 */
	public function withKeyResolver(callable $keyResolver): UrlGenerator
	{
		return (clone $this)->setKeyResolver($keyResolver);
	}
}
