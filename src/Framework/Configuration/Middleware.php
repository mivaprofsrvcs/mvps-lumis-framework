<?php

namespace MVPS\Lumis\Framework\Configuration;

use Closure;
use MVPS\Lumis\Framework\Http\Middleware\ConvertEmptyStringsToNull;
use MVPS\Lumis\Framework\Support\Arr;

class Middleware
{
	/**
	 * Indicates the API middleware group's rate limiter.
	 *
	 * @var string|null
	 */
	protected string|null $apiLimiter = null;

	/**
	 * The middleware that should be appended to the global middleware stack.
	 *
	 * @var array
	 */
	protected array $appends = [];

	/**
	 * Indicates if sessions should be authenticated for the "web" middleware group.
	 *
	 * @var bool
	 */
	protected bool $authenticatedSessions = false;

	/**
	 * The custom middleware aliases.
	 *
	 * @var array
	 */
	protected array $customAliases = [];

	/**
	 * The user defined global middleware stack.
	 *
	 * @var array
	 */
	protected array $global = [];

	/**
	 * The middleware that should be appended to the specified groups.
	 *
	 * @var array
	 */
	protected array $groupAppends = [];

	/**
	 * The middleware that should be prepended to the specified groups.
	 *
	 * @var array
	 */
	protected array $groupPrepends = [];

	/**
	 * The middleware that should be removed from the specified groups.
	 *
	 * @var array
	 */
	protected array $groupRemovals = [];

	/**
	 * The middleware that should be replaced in the specified groups.
	 *
	 * @var array
	 */
	protected array $groupReplacements = [];

	/**
	 * The user defined middleware groups.
	 *
	 * @var array
	 */
	protected array $groups = [];

	/**
	 * The Folio / page middleware for the application.
	 *
	 * @var array
	 */
	protected array $pageMiddleware = [];

	/**
	 * The middleware that should be prepended to the global middleware stack.
	 *
	 * @var array
	 */
	protected array $prepends = [];

	/**
	 * The custom middleware priority definition.
	 *
	 * @var array
	 */
	protected array $priority = [];

	/**
	 * The middleware that should be removed from the global middleware stack.
	 *
	 * @var array
	 */
	protected array $removals = [];

	/**
	 * The middleware that should be replaced in the global middleware stack.
	 *
	 * @var array
	 */
	protected array $replacements = [];

	/**
	 * Indicates if the "trust hosts" middleware is enabled.
	 *
	 * @var bool
	 */
	protected bool $trustHosts = false;

	/**
	 * Register additional middleware aliases.
	 */
	public function alias(array $aliases): static
	{
		$this->customAliases = $aliases;

		return $this;
	}

	/**
	 * Modify the middleware in the "api" group.
	 */
	public function api(
		array|string $append = [],
		array|string $prepend = [],
		array|string $remove = [],
		array $replace = []
	): static {
		return $this->modifyGroup('api', $append, $prepend, $remove, $replace);
	}

	/**
	 * Append middleware to the application's global middleware stack.
	 */
	public function append(array|string $middleware): static
	{
		$this->appends = array_merge(
			$this->appends,
			Arr::wrap($middleware)
		);

		return $this;
	}

	/**
	 * Append the given middleware to the specified group.
	 */
	public function appendToGroup(string $group, array|string $middleware): static
	{
		$this->groupAppends[$group] = array_merge(
			$this->groupAppends[$group] ?? [],
			Arr::wrap($middleware)
		);

		return $this;
	}

	/**
	 * Indicate that sessions should be authenticated for the "web" middleware group.
	 */
	public function authenticateSessions(): static
	{
		$this->authenticatedSessions = true;

		return $this;
	}

	/**
	 * Configure the empty string conversion middleware.
	 */
	public function convertEmptyStringsToNull(array $except = []): static
	{
		collection($except)
			->each(fn (Closure $callback) => ConvertEmptyStringsToNull::skipWhen($callback));

		return $this;
	}

	/**
	 * Get the default middleware aliases.
	 *
	 * TODO: Implement this
	 */
	protected function defaultAliases(): array
	{
		$aliases = [
			// 'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
			// 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
			// 'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
			// 'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
			// 'can' => \Illuminate\Auth\Middleware\Authorize::class,
			// 'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
			// 'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
			// 'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
			// 'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
			// 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
			// 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
		];

		return $aliases;
	}

	/**
	 * Configure the cookie encryption middleware.
	 *
	 * TODO: Implement this
	 */
	public function encryptCookies(array $except = []): static
	{
		// EncryptCookies::except($except);

		return $this;
	}

	/**
	 * Get the global middleware.
	 */
	public function getGlobalMiddleware(): array
	{
		$middleware = $this->global ?: array_values(array_filter([
			// $this->trustHosts ? TrustHosts::class : null,
			// TrustProxies::class,
			// HandleCors::class,
			// PreventRequestsDuringMaintenance::class,
			// ValidatePostSize::class,
			// TrimStrings::class,
			ConvertEmptyStringsToNull::class,
		]));

		$middleware = array_map(function ($middleware) {
			return $this->replacements[$middleware] ?? $middleware;
		}, $middleware);

		return array_values(array_filter(array_diff(
			array_unique(array_merge($this->prepends, $middleware, $this->appends)),
			$this->removals
		)));
	}

	/**
	 * Get the middleware aliases.
	 */
	public function getMiddlewareAliases(): array
	{
		return array_merge($this->defaultAliases(), $this->customAliases);
	}

	/**
	 * Get the middleware groups.
	 *
	 * TODO: Implement more groups
	 */
	public function getMiddlewareGroups(): array
	{
		$middleware = [
			'web' => array_values(array_filter([
				// \Illuminate\Cookie\Middleware\EncryptCookies::class,
				// \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
				// \Illuminate\Session\Middleware\StartSession::class,
				// \Illuminate\View\Middleware\ShareErrorsFromSession::class,
				// \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
				\MVPS\Lumis\Framework\Routing\Middleware\SubstituteBindings::class,
				// $this->authenticatedSessions ? 'auth.session' : null,
			])),

			// TODO: Look at implementing this
			// 'api' => array_values(array_filter([
			// 	$this->apiLimiter ? 'throttle:'.$this->apiLimiter : null,
			// 	\Illuminate\Routing\Middleware\SubstituteBindings::class,
			// ])),
		];

		$middleware = array_merge($middleware, $this->groups);

		foreach ($middleware as $group => $groupedMiddleware) {
			foreach ($groupedMiddleware as $index => $groupMiddleware) {
				if (isset($this->groupReplacements[$group][$groupMiddleware])) {
					$middleware[$group][$index] = $this->groupReplacements[$group][$groupMiddleware];
				}
			}
		}

		foreach ($this->groupRemovals as $group => $removals) {
			$middleware[$group] = array_values(array_filter(
				array_diff($middleware[$group] ?? [], $removals)
			));
		}

		foreach ($this->groupPrepends as $group => $prepends) {
			$middleware[$group] = array_values(array_filter(
				array_unique(array_merge($prepends, $middleware[$group] ?? []))
			));
		}

		foreach ($this->groupAppends as $group => $appends) {
			$middleware[$group] = array_values(array_filter(
				array_unique(array_merge($middleware[$group] ?? [], $appends))
			));
		}

		return $middleware;
	}

	/**
	 * Get the middleware priority for the application.
	 */
	public function getMiddlewarePriority(): array
	{
		return $this->priority;
	}

	/**
	 * Get the page middleware for the application.
	 */
	public function getPageMiddleware(): array
	{
		return $this->pageMiddleware;
	}

	/**
	 * Define a middleware group.
	 */
	public function group(string $group, array $middleware): static
	{
		$this->groups[$group] = $middleware;

		return $this;
	}

	/**
	 * Modify the middleware in the given group.
	 */
	protected function modifyGroup(
		string $group,
		array|string $append,
		array|string $prepend,
		array|string $remove,
		array $replace
	): static {
		if (! empty($append)) {
			$this->appendToGroup($group, $append);
		}

		if (! empty($prepend)) {
			$this->prependToGroup($group, $prepend);
		}

		if (! empty($remove)) {
			$this->removeFromGroup($group, $remove);
		}

		if (! empty($replace)) {
			foreach ($replace as $search => $replace) {
				$this->replaceInGroup($group, $search, $replace);
			}
		}

		return $this;
	}

	/**
	 * Register the Folio / page middleware for the application.
	 */
	public function pages(array $middleware): static
	{
		$this->pageMiddleware = $middleware;

		return $this;
	}

	/**
	 * Prepend middleware to the application's global middleware stack.
	 */
	public function prepend(array|string $middleware): static
	{
		$this->prepends = array_merge(
			Arr::wrap($middleware),
			$this->prepends
		);

		return $this;
	}

	/**
	 * Prepend the given middleware to the specified group.
	 */
	public function prependToGroup(string $group, array|string $middleware): static
	{
		$this->groupPrepends[$group] = array_merge(
			Arr::wrap($middleware),
			$this->groupPrepends[$group] ?? []
		);

		return $this;
	}

	/**
	 * Configure the middleware that prevents requests during maintenance mode.
	 *
	 * TODO: Implement this
	 */
	public function preventRequestsDuringMaintenance(array $except = []): static
	{
		// PreventRequestsDuringMaintenance::except($except);

		return $this;
	}

	/**
	 * Define the middleware priority for the application.
	 */
	public function priority(array $priority): static
	{
		$this->priority = $priority;

		return $this;
	}

	/**
	 * Configure where guests are redirected by the "auth" middleware.
	 */
	public function redirectGuestsTo(callable|string $redirect): static
	{
		return $this->redirectTo(guests: $redirect);
	}

	/**
	 * Configure where users are redirected by the authentication and guest middleware.
	 *
	 * TODO: Implement this
	 */
	public function redirectTo(callable|string|null $guests = null, callable|string|null $users = null): static
	{
		// $guests = is_string($guests) ? fn () => $guests : $guests;
		// $users = is_string($users) ? fn () => $users : $users;

		// if ($guests) {
		// 	Authenticate::redirectUsing($guests);
		// 	AuthenticateSession::redirectUsing($guests);
		// 	AuthenticationException::redirectUsing($guests);
		// }

		// if ($users) {
		// 	RedirectIfAuthenticated::redirectUsing($users);
		// }

		return $this;
	}

	/**
	 * Configure where users are redirected by the "guest" middleware.
	 */
	public function redirectUsersTo(callable|string $redirect): static
	{
		return $this->redirectTo(users: $redirect);
	}

	/**
	 * Remove middleware from the application's global middleware stack.
	 */
	public function remove(array|string $middleware): static
	{
		$this->removals = array_merge(
			$this->removals,
			Arr::wrap($middleware)
		);

		return $this;
	}

	/**
	 * Remove the given middleware from the specified group.
	 */
	public function removeFromGroup(string $group, array|string $middleware): static
	{
		$this->groupRemovals[$group] = array_merge(
			Arr::wrap($middleware),
			$this->groupRemovals[$group] ?? []
		);

		return $this;
	}

	/**
	 * Specify a middleware that should be replaced with another middleware.
	 */
	public function replace(string $search, string $replace): static
	{
		$this->replacements[$search] = $replace;

		return $this;
	}

	/**
	 * Replace the given middleware in the specified group with another middleware.
	 */
	public function replaceInGroup(string $group, string $search, string $replace): static
	{
		$this->groupReplacements[$group][$search] = $replace;

		return $this;
	}

	/**
	 * Indicate that the API middleware group's throttling middleware should be enabled.
	 */
	public function throttleApi(string $limiter = 'api'): static
	{
		$this->apiLimiter = $limiter;

		return $this;
	}

	/**
	 * Configure the string trimming middleware.
	 *
	 * TODO: Implement this
	 */
	public function trimStrings(array $except = []): static
	{
		// [$skipWhen, $except] = collection($except)->partition(fn ($value) => $value instanceof Closure);

		// $skipWhen->each(fn (Closure $callback) => TrimStrings::skipWhen($callback));

		// TrimStrings::except($except->all());

		return $this;
	}

	/**
	 * Indicate that the trusted host middleware should be enabled.
	 *
	 * TODO: Implement this
	 */
	public function trustHosts(array|callable|null $at = null, bool $subdomains = true): static
	{
		// $this->trustHosts = true;

		// if (! is_null($at)) {
		// 	TrustHosts::at($at, $subdomains);
		// }

		return $this;
	}

	/**
	 * Configure the trusted proxies for the application.
	 *
	 * TODO: Implement this
	 */
	public function trustProxies(array|string|null $at = null, int|null $headers = null): static
	{
		// if (! is_null($at)) {
		// 	TrustProxies::at($at);
		// }

		// if (! is_null($headers)) {
		// 	TrustProxies::withHeaders($headers);
		// }

		return $this;
	}

	/**
	 * Define the global middleware for the application.
	 */
	public function use(array $middleware): static
	{
		$this->global = $middleware;

		return $this;
	}

	/**
	 * Configure the CSRF token validation middleware.
	 *
	 * TODO: Implement this
	 */
	public function validateCsrfTokens(array $except = []): static
	{
		// ValidateCsrfToken::except($except);

		return $this;
	}

	/**
	 * Configure the URL signature validation middleware.
	 *
	 * TODO: Implement this
	 */
	public function validateSignatures(array $except = []): static
	{
		// ValidateSignature::except($except);

		return $this;
	}

	/**
	 * Modify the middleware in the "web" group.
	 */
	public function web(
		array|string $append = [],
		array|string $prepend = [],
		array|string $remove = [],
		array $replace = []
	): static {
		return $this->modifyGroup('web', $append, $prepend, $remove, $replace);
	}
}
