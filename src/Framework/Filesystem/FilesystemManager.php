<?php

namespace MVPS\Lumis\Framework\Filesystem;

use Closure;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use MVPS\Lumis\Framework\Contracts\Filesystem\Factory as FactoryContract;
use MVPS\Lumis\Framework\Contracts\Filesystem\Filesystem;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use MVPS\Lumis\Framework\Support\Arr;

class FilesystemManager implements FactoryContract
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
	 * The array of resolved filesystem drivers.
	 *
	 * @var array
	 */
	protected array $disks = [];

	/**
	 * Create a new filesystem manager instance.
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Build an on-demand disk.
	 */
	public function build(string|array $config): Filesystem
	{
		return $this->resolve('ondemand', is_array($config) ? $config : [
			'driver' => 'local',
			'root' => $config,
		]);
	}

	/**
	 * Call a custom driver creator.
	 */
	protected function callCustomCreator(array $config): Filesystem
	{
		return $this->customCreators[$config['driver']]($this->app, $config);
	}

	/**
	 * Create a Flysystem instance with the given adapter.
	 */
	protected function createFlysystem(FlysystemAdapter $adapter, array $config): FilesystemOperator
	{
		if ($config['read-only'] ?? false === true) {
			$adapter = new ReadOnlyFilesystemAdapter($adapter);
		}

		if (! empty($config['prefix'])) {
			$adapter = new PathPrefixedAdapter($adapter, $config['prefix']);
		}

		return new Flysystem($adapter, Arr::only($config, [
			'directory_visibility',
			'disable_asserts',
			'retain_visibility',
			'temporary_url',
			'url',
			'visibility',
		]));
	}

	/**
	 * Create an instance of the FTP driver.
	 */
	public function createFtpDriver(array $config): Filesystem
	{
		if (! isset($config['root'])) {
			$config['root'] = '';
		}

		$adapter = new FtpAdapter(FtpConnectionOptions::fromArray($config));

		return new FilesystemAdapter(
			$this->createFlysystem($adapter, $config),
			$adapter,
			$config
		);
	}

	/**
	 * Create an instance of the local driver.
	 */
	public function createLocalDriver(array $config, string $name = 'local'): Filesystem
	{
		$visibility = PortableVisibilityConverter::fromArray(
			$config['permissions'] ?? [],
			$config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
		);

		$links = ($config['links'] ?? null) === 'skip'
			? LocalAdapter::SKIP_LINKS
			: LocalAdapter::DISALLOW_LINKS;

		$adapter = new LocalAdapter(
			$config['root'],
			$visibility,
			$config['lock'] ?? LOCK_EX,
			$links
		);

		return (new LocalFilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config))
			->diskName($name)
			->shouldServeSignedUrls($config['serve'] ?? false, fn () => $this->app['url']);
	}

	/**
	 * Create a scoped driver.
	 */
	public function createScopedDriver(array $config): Filesystem
	{
		if (empty($config['disk'])) {
			throw new InvalidArgumentException('Scoped disk is missing "disk" configuration option.');
		} elseif (empty($config['prefix'])) {
			throw new InvalidArgumentException('Scoped disk is missing "prefix" configuration option.');
		}

		return $this->build(tap(
			is_string($config['disk']) ? $this->getConfig($config['disk']) : $config['disk'],
			function (&$parent) use ($config) {
				$parent['prefix'] = $config['prefix'];

				if (isset($config['visibility'])) {
					$parent['visibility'] = $config['visibility'];
				}
			}
		));
	}

	/**
	 * Create an instance of the SFTP driver.
	 */
	public function createSftpDriver(array $config): Filesystem
	{
		$provider = SftpConnectionProvider::fromArray($config);

		$root = $config['root'] ?? '';

		$visibility = PortableVisibilityConverter::fromArray(
			$config['permissions'] ?? []
		);

		$adapter = new SftpAdapter($provider, $root, $visibility);

		return new FilesystemAdapter(
			$this->createFlysystem($adapter, $config),
			$adapter,
			$config
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function disk($name = null): Filesystem
	{
		$name = $name ?: $this->getDefaultDriver();

		return $this->disks[$name] = $this->get($name);
	}

	/**
	 * Get a filesystem instance.
	 */
	public function drive(string|null $name = null): Filesystem
	{
		return $this->disk($name);
	}

	/**
	 * Register a custom driver creator Closure.
	 */
	public function extend(string $driver, Closure $callback): static
	{
		$this->customCreators[$driver] = $callback;

		return $this;
	}

	/**
	 * Unset the given disk instances.
	 */
	public function forgetDisk(array|string $disk): static
	{
		foreach ((array) $disk as $diskName) {
			unset($this->disks[$diskName]);
		}

		return $this;
	}

	/**
	 * Attempt to get the disk from the local cache.
	 */
	protected function get(string $name): Filesystem
	{
		return $this->disks[$name] ?? $this->resolve($name);
	}

	/**
	 * Get the filesystem connection configuration.
	 */
	protected function getConfig(string $name): array
	{
		return $this->app['config']["filesystems.disks.{$name}"] ?: [];
	}

	/**
	 * Get the default driver name.
	 */
	public function getDefaultDriver(): string
	{
		return $this->app['config']['filesystems.default'];
	}

	/**
	 * Disconnect the given disk and remove from local cache.
	 */
	public function purge(string|null $name = null): void
	{
		$name ??= $this->getDefaultDriver();

		unset($this->disks[$name]);
	}

	/**
	 * Resolve the given disk.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolve(string $name, array|null $config = null): Filesystem
	{
		$config ??= $this->getConfig($name);

		if (empty($config['driver'])) {
			throw new InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
		}

		$driver = $config['driver'];

		if (isset($this->customCreators[$driver])) {
			return $this->callCustomCreator($config);
		}

		$driverMethod = 'create' . ucfirst($driver) . 'Driver';

		if (! method_exists($this, $driverMethod)) {
			throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
		}

		return $this->{$driverMethod}($config, $name);
	}

	/**
	 * Set the given disk instance.
	 */
	public function set(string $name, mixed $disk): static
	{
		$this->disks[$name] = $disk;

		return $this;
	}

	/**
	 * Set the application instance used by the manager.
	 */
	public function setApplication(Application $app)
	{
		$this->app = $app;

		return $this;
	}

	/**
	 * Dynamically call the default driver instance.
	 */
	public function __call(string $method, array $parameters): mixed
	{
		return $this->disk()->$method(...$parameters);
	}
}
