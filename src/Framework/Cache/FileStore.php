<?php

namespace MVPS\Lumis\Framework\Cache;

use Exception;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Filesystem\LockableFile;
use Illuminate\Support\InteractsWithTime;
use MVPS\Lumis\Framework\Cache\Traits\RetrievesMultipleKeys;
use MVPS\Lumis\Framework\Filesystem\Filesystem;

class FileStore implements Store, LockProvider
{
	use InteractsWithTime;
	use RetrievesMultipleKeys;

	/**
	 * The file cache directory.
	 *
	 * @var string
	 */
	protected string $directory;

	/**
	 * Octal representation of the cache file permissions.
	 *
	 * @var int|null
	 */
	protected int|null $filePermission;

	/**
	 * The Filesystem instance.
	 *
	 * @var \MVPS\Lumis\Framework\Filesystem\Filesystem
	 */
	protected Filesystem $files;

	/**
	 * The file cache lock directory.
	 *
	 * @var string|null
	 */
	protected string|null $lockDirectory = null;

	/**
	 * Create a new file cache store instance.
	 */
	public function __construct(Filesystem $files, string $directory, int|null $filePermission = null)
	{
		$this->files = $files;
		$this->directory = $directory;
		$this->filePermission = $filePermission;
	}

	/**
	 * {@inheritdoc}
	 */
	public function add($key, $value, $seconds)
	{
		$path = $this->path($key);

		$this->ensureCacheDirectoryExists($path);

		$file = new LockableFile($path, 'c+');

		try {
			$file->getExclusiveLock();
		} catch (LockTimeoutException $e) {
			$file->close();

			return false;
		}

		$expire = $file->read(10);

		if (empty($expire) || $this->currentTime() >= $expire) {
			$file->truncate()
				->write($this->expiration($seconds) . serialize($value))
				->close();

			$this->ensurePermissionsAreCorrect($path);

			return true;
		}

		$file->close();

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function decrement($key, $value = 1)
	{
		return $this->increment($key, $value * -1);
	}

	/**
	 * Get a default empty payload for the cache.
	 */
	protected function emptyPayload(): array
	{
		return ['data' => null, 'time' => null];
	}

	/**
	 * Create the file cache directory if necessary.
	 */
	protected function ensureCacheDirectoryExists(string $path): void
	{
		$directory = dirname($path);

		if (! $this->files->exists($directory)) {
			$this->files->makeDirectory($directory, 0777, true, true);

			$this->ensurePermissionsAreCorrect($directory);

			$this->ensurePermissionsAreCorrect(dirname($directory));
		}
	}

	/**
	 * Ensure the created node has the correct permissions.
	 */
	protected function ensurePermissionsAreCorrect(string $path): void
	{
		if (is_null($this->filePermission) || intval($this->files->chmod($path), 8) === $this->filePermission) {
			return;
		}

		$this->files->chmod($path, $this->filePermission);
	}

	/**
	 * Get the expiration time based on the given seconds.
	 */
	protected function expiration(int $seconds): int
	{
		$time = $this->availableAt($seconds);

		return $seconds === 0 || $time > 9999999999 ? 9999999999 : $time;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush()
	{
		if (! $this->files->isDirectory($this->directory)) {
			return false;
		}

		foreach ($this->files->directories($this->directory) as $directory) {
			$deleted = $this->files->deleteDirectory($directory);

			if (! $deleted || $this->files->exists($directory)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function forever($key, $value)
	{
		return $this->put($key, $value, 0);
	}

	/**
	 * {@inheritdoc}
	 */
	public function forget($key)
	{
		$file = $this->path($key);

		return $this->files->exists($file)
			? $this->files->delete($file)
			: false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		return $this->getPayload($key)['data'] ?? null;
	}

	/**
	 * Get the working directory of the cache.
	 */
	public function getDirectory(): string
	{
		return $this->directory;
	}

	/**
	 * Get the Filesystem instance.
	 */
	public function getFilesystem(): Filesystem
	{
		return $this->files;
	}

	/**
	 * Retrieve an item and expiry time from the cache by key.
	 */
	protected function getPayload(string $key): array
	{
		$path = $this->path($key);

		try {
			$contents = $this->files->get($path, true);

			if (is_null($contents)) {
				return $this->emptyPayload();
			}

			$expire = substr($contents, 0, 10);
		} catch (Exception $e) {
			return $this->emptyPayload();
		}

		if ($this->currentTime() >= $expire) {
			$this->forget($key);

			return $this->emptyPayload();
		}

		try {
			$data = unserialize(substr($contents, 10));
		} catch (Exception) {
			$this->forget($key);

			return $this->emptyPayload();
		}

		$time = $expire - $this->currentTime();

		return compact('data', 'time');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPrefix()
	{
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function increment($key, $value = 1)
	{
		$raw = $this->getPayload($key);

		return tap(
			((int) $raw['data']) + $value,
			fn ($newValue) => $this->put($key, $newValue, $raw['time'] ?? 0)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function lock($name, $seconds = 0, $owner = null)
	{
		$this->ensureCacheDirectoryExists($this->lockDirectory ?? $this->directory);

		return new FileLock(
			new static($this->files, $this->lockDirectory ?? $this->directory, $this->filePermission),
			$name,
			$seconds,
			$owner
		);
	}

	/**
	 * Get the full path for the given cache key.
	 */
	public function path(string $key): string
	{
		$hash = sha1($key);

		$parts = array_slice(str_split($hash, 2), 0, 2);

		return $this->directory . '/' . implode('/', $parts) . '/' . $hash;
	}

	/**
	 * {@inheritdoc}
	 */
	public function put($key, $value, $seconds)
	{
		$path = $this->path($key);

		$this->ensureCacheDirectoryExists($path);

		$result = $this->files->put(
			$path,
			$this->expiration($seconds) . serialize($value),
			true
		);

		if ($result !== false && $result > 0) {
			$this->ensurePermissionsAreCorrect($path);

			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function restoreLock($name, $owner)
	{
		return $this->lock($name, 0, $owner);
	}

	/**
	 * Set the working directory of the cache.
	 */
	public function setDirectory(string $directory): static
	{
		$this->directory = $directory;

		return $this;
	}

	/**
	 * Set the cache directory where locks should be stored.
	 */
	public function setLockDirectory(string|null $lockDirectory): static
	{
		$this->lockDirectory = $lockDirectory;

		return $this;
	}
}
