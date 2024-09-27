<?php

namespace MVPS\Lumis\Framework\Cache;

use Closure;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Cache\Store;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Contracts\Cache\Repository as RepositoryContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher as DispatcherContract;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Support\Arr;

class CacheManager implements FactoryContract
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application $app;

	/**
	 * The registered custom driver creators.
	 *
	 * @var array
	 */
	protected array $customCreators = [];

	/**
	 * The array of resolved cache stores.
	 *
	 * @var array
	 */
	protected array $stores = [];

	/**
	 * Create a new cache manager instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Call a custom driver creator.
	 */
	protected function callCustomCreator(array $config)
	{
		return $this->customCreators[$config['driver']]($this->app, $config);
	}

	/**
	 * Create an instance of the APC cache driver.
	 */
	protected function createApcDriver(array $config): Repository
	{
		$prefix = $this->getPrefix($config);

		return $this->repository(new ApcStore(new ApcWrapper, $prefix), $config);
	}

	/**
	 * Create an instance of the array cache driver.
	 */
	protected function createArrayDriver(array $config): Repository
	{
		return $this->repository(new ArrayStore($config['serialize'] ?? false), $config);
	}

	/**
	 * Create an instance of the database cache driver.
	 */
	protected function createDatabaseDriver(array $config): Repository
	{
		$connection = $this->app['db']->connection($config['connection'] ?? null);

		$store = new DatabaseStore(
			$connection,
			$config['table'],
			$this->getPrefix($config),
			$config['lock_table'] ?? 'cache_locks',
			$config['lock_lottery'] ?? [2, 100],
			$config['lock_timeout'] ?? 86400,
		);

		return $this->repository(
			$store->setLockConnection(
				$this->app['db']->connection($config['lock_connection'] ?? $config['connection'] ?? null)
			),
			$config
		);
	}

	/**
	 * Create an instance of the file cache driver.
	 */
	protected function createFileDriver(array $config): Repository
	{
		return $this->repository(
			(new FileStore($this->app['files'], $config['path'], $config['permission'] ?? null))
				->setLockDirectory($config['lock_path'] ?? null),
			$config
		);
	}

	/**
	 * Create an instance of the Null cache driver.
	 */
	protected function createNullDriver(): Repository
	{
		return $this->repository(new NullStore, []);
	}

	/**
	 * Get a cache driver instance.
	 */
	public function driver(string|null $driver = null): RepositoryContract
	{
		return $this->store($driver);
	}

	/**
	 * Register a custom driver creator Closure.
	 */
	public function extend(string $driver, Closure $callback): static
	{
		$this->customCreators[$driver] = $callback->bindTo($this, $this);

		return $this;
	}

	/**
	 * Unset the given driver instances.
	 */
	public function forgetDriver(array|string|null $name = null): static
	{
		$name ??= $this->getDefaultDriver();

		foreach ((array) $name as $cacheName) {
			if (! isset($this->stores[$cacheName])) {
				continue;
			}

			unset($this->stores[$cacheName]);
		}

		return $this;
	}

	/**
	 * Get the default cache driver name.
	 */
	public function getDefaultDriver(): string
	{
		return $this->app['config']['cache.default'];
	}

	/**
	 * Get the cache connection configuration.
	 */
	protected function getConfig(string $name): array|null
	{
		if (! is_null($name) && $name !== 'null') {
			return $this->app['config']["cache.stores.{$name}"];
		}

		return ['driver' => 'null'];
	}

	/**
	 * Get the cache prefix.
	 */
	protected function getPrefix(array $config): string
	{
		return $config['prefix'] ?? $this->app['config']['cache.prefix'];
	}

	/**
	 * Disconnect the given driver and remove from local cache.
	 */
	public function purge(string|null $name = null): void
	{
		$name ??= $this->getDefaultDriver();

		unset($this->stores[$name]);
	}

	/**
	 * Re-set the event dispatcher on all resolved cache repositories.
	 */
	public function refreshEventDispatcher(): void
	{
		array_map([$this, 'setEventDispatcher'], $this->stores);
	}

	/**
	 * Create a new cache repository with the given implementation.
	 */
	public function repository(Store $store, array $config = []): Repository
	{
		return tap(
			new Repository($store, Arr::only($config, ['store'])),
			function ($repository) use ($config) {
				if ($config['events'] ?? true) {
					$this->setEventDispatcher($repository);
				}
			}
		);
	}

	/**
	 * Resolve the given store.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function resolve(string $name): RepositoryContract
	{
		$config = $this->getConfig($name);

		if (is_null($config)) {
			throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
		}

		$config = Arr::add($config, 'store', $name);

		if (isset($this->customCreators[$config['driver']])) {
			return $this->callCustomCreator($config);
		}

		$driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

		if (method_exists($this, $driverMethod)) {
			return $this->{$driverMethod}($config);
		}

		throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
	}

	/**
	 * Set the application instance used by the manager.
	 */
	public function setApplication(Application $app): static
	{
		$this->app = $app;

		return $this;
	}

	/**
	 * Set the default cache driver name.
	 */
	public function setDefaultDriver(string $name): void
	{
		$this->app['config']['cache.default'] = $name;
	}

	/**
	 * Set the event dispatcher on the given repository instance.
	 */
	protected function setEventDispatcher(Repository $repository): void
	{
		if (! $this->app->bound(DispatcherContract::class)) {
			return;
		}

		$repository->setEventDispatcher($this->app[DispatcherContract::class]);
	}

	/**
	 * Get a cache store instance by name, wrapped in a repository.
	 */
	public function store($name = null): RepositoryContract
	{
		$name = $name ?: $this->getDefaultDriver();

		return $this->stores[$name] ??= $this->resolve($name);
	}

	/**
	 * Dynamically call the default driver instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->store()->$method(...$parameters);
	}
}
