<?php

namespace MVPS\Lumis\Framework\Filesystem;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use MVPS\Lumis\Framework\Contracts\Filesystem\Filesystem as FilesystemContract;
use MVPS\Lumis\Framework\Http\File;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Http\StreamedResponse;
use MVPS\Lumis\Framework\Http\UploadedFile;
use MVPS\Lumis\Framework\Support\Str;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class FilesystemAdapter implements FilesystemContract
{
	use Conditionable;
	use Macroable {
		__call as macroCall;
	}

	/**
	 * The Flysystem adapter implementation.
	 *
	 * @var \League\Flysystem\FilesystemAdapter
	 */
	protected FlysystemAdapter $adapter;

	/**
	 * The filesystem configuration.
	 *
	 * @var array
	 */
	protected array $config;

	/**
	 * The Flysystem filesystem implementation.
	 *
	 * @var \League\Flysystem\FilesystemOperator
	 */
	protected FilesystemOperator $driver;

	/**
	 * The Flysystem PathPrefixer instance.
	 *
	 * @var \League\Flysystem\PathPrefixer
	 */
	protected PathPrefixer $prefixer;

	/**
	 * The file server callback.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $serveCallback = null;

	/**
	 * The temporary URL builder callback.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $temporaryUrlCallback = null;

	/**
	 * Create a new filesystem adapter instance.
	 */
	public function __construct(FilesystemOperator $driver, FlysystemAdapter $adapter, array $config = [])
	{
		$this->driver = $driver;
		$this->adapter = $adapter;
		$this->config = $config;

		$separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;

		$this->prefixer = isset($config['prefix'])
			? new PathPrefixer($this->prefixer->prefixPath($config['prefix']), $separator)
			: new PathPrefixer($config['root'] ?? '', $separator);
	}

	/**
	 * {@inheritdoc}
	 */
	public function allDirectories($directory = null): array
	{
		return $this->directories($directory, true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function allFiles($directory = null): array
	{
		return $this->files($directory, true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function append($path, $data, $separator = PHP_EOL): bool
	{
		return $this->fileExists($path)
			? $this->put($path, $this->get($path) . $separator . $data)
			: $this->put($path, $data);
	}

	/**
	 * Define a custom temporary URL builder callback.
	 */
	public function buildTemporaryUrlsUsing(Closure $callback): void
	{
		$this->temporaryUrlCallback = $callback;
	}

	/**
	 * Get the checksum for a file.
	 *
	 * @throws \League\Flysystem\UnableToProvideChecksum
	 */
	public function checksum(string $path, array $options = []): string|false
	{
		try {
			return $this->driver->checksum($path, $options);
		} catch (UnableToProvideChecksum $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}
	}

	/**
	 * Concatenate a path to a URL.
	 */
	protected function concatPathToUrl(string $url, string $path): string
	{
		return rtrim($url, '/') . '/' . ltrim($path, '/');
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToCopyFile
	 */
	public function copy($from, $to): bool
	{
		try {
			$this->driver->copy($from, $to);
		} catch (UnableToCopyFile $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToDeleteFile
	 */
	public function delete($paths): bool
	{
		$paths = is_array($paths) ? $paths : func_get_args();

		$success = true;

		foreach ($paths as $path) {
			try {
				$this->driver->delete($path);
			} catch (UnableToDeleteFile $e) {
				throw_if($this->throwsExceptions(), $e);

				$success = false;
			}
		}

		return $success;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToDeleteDirectory
	 */
	public function deleteDirectory($directory): bool
	{
		try {
			$this->driver->deleteDirectory($directory);
		} catch (UnableToDeleteDirectory $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function directories($directory = null, $recursive = false): array
	{
		return $this->driver->listContents($directory ?? '', $recursive)
			->filter(fn (StorageAttributes $attributes) => $attributes->isDir())
			->map(fn (StorageAttributes $attributes) => $attributes->path())
			->toArray();
	}

	/**
	 * Determine if a directory exists.
	 */
	public function directoryExists(string $path): bool
	{
		return $this->driver->directoryExists($path);
	}

	/**
	 * Determine if a directory is missing.
	 */
	public function directoryMissing(string $path): bool
	{
		return ! $this->directoryExists($path);
	}

	/**
	 * Create a streamed download response for a given file.
	 */
	public function download(string $path, string|null $name = null, array $headers = []): StreamedResponse
	{
		return $this->response($path, $name, $headers, 'attachment');
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists($path): bool
	{
		return $this->driver->has($path);
	}

	/**
	 * Convert the string to ASCII characters that are equivalent to the given name.
	 */
	protected function fallbackName(string $name): string
	{
		return str_replace('%', '', Str::ascii($name));
	}

	/**
	 * Determine if a file exists.
	 */
	public function fileExists(string $path): bool
	{
		return $this->driver->fileExists($path);
	}

	/**
	 * Determine if a file is missing.
	 */
	public function fileMissing(string $path): bool
	{
		return ! $this->fileExists($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function files($directory = null, $recursive = false): array
	{
		return $this->driver->listContents($directory ?? '', $recursive)
			->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
			->sortByPath()
			->map(fn (StorageAttributes $attributes) => $attributes->path())
			->toArray();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToReadFile
	 */
	public function get($path): string|null
	{
		try {
			return $this->driver->read($path);
		} catch (UnableToReadFile $e) {
			throw_if($this->throwsExceptions(), $e);

			return null;
		}
	}

	/**
	 * Get the Flysystem adapter.
	 */
	public function getAdapter(): FlysystemAdapter
	{
		return $this->adapter;
	}

	/**
	 * Get the configuration values.
	 */
	public function getConfig(): array
	{
		return $this->config;
	}

	/**
	 * Get the Flysystem driver.
	 */
	public function getDriver(): FilesystemOperator
	{
		return $this->driver;
	}

	/**
	 * Get the URL for the file at the given path.
	 */
	protected function getFtpUrl(string $path): string
	{
		return isset($this->config['url'])
			? $this->concatPathToUrl($this->config['url'], $path)
			: $path;
	}

	/**
	 * Get the URL for the file at the given path.
	 */
	protected function getLocalUrl(string $path): string
	{
		if (isset($this->config['url'])) {
			return $this->concatPathToUrl($this->config['url'], $path);
		}

		$path = '/storage/' . $path;
		$publicPath = '/public/';

		if (str_contains($path, '/storage' . $publicPath)) {
			return Str::replaceFirst($publicPath, '/', $path);
		}

		return $path;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getVisibility($path): string
	{
		return $this->driver->visibility($path) === Visibility::PUBLIC
			? FilesystemContract::VISIBILITY_PUBLIC
			: FilesystemContract::VISIBILITY_PRIVATE;
	}

	/**
	 * Get the contents of a file as decoded JSON.
	 */
	public function json(string $path, int $flags = 0): array|null
	{
		$content = $this->get($path);

		return is_null($content)
			? null
			: json_decode($content, true, 512, $flags);
	}

	/**
	 * {@inheritdoc}
	 */
	public function lastModified($path): int
	{
		return $this->driver->lastModified($path);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToCreateDirectory
	 * @throws \League\Flysystem\UnableToSetVisibility
	 */
	public function makeDirectory($path): bool
	{
		try {
			$this->driver->createDirectory($path);
		} catch (RuntimeException $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @throws \League\Flysystem\UnableToRetrieveMetadata
	 */
	public function mimeType(string $path): string|false
	{
		try {
			return $this->driver->mimeType($path);
		} catch (UnableToRetrieveMetadata $e) {
			throw_if($this->throwsExceptions(), $e);
		}

		return false;
	}

	/**
	 * Determine if a file or directory is missing.
	 */
	public function missing(string $path): bool
	{
		return ! $this->exists($path);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToMoveFile
	 */
	public function move($from, $to): bool
	{
		try {
			$this->driver->move($from, $to);
		} catch (UnableToMoveFile $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function path($path): string
	{
		return $this->prefixer->prefixPath($path);
	}

	/**
	 * Parse the given visibility value.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function parseVisibility(string|null $visibility): string|null
	{
		if (is_null($visibility)) {
			return null;
		}

		return match ($visibility) {
			FilesystemContract::VISIBILITY_PUBLIC => Visibility::PUBLIC,
			FilesystemContract::VISIBILITY_PRIVATE => Visibility::PRIVATE,
			default => throw new InvalidArgumentException("Unknown visibility [$visibility]."),
		};
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepend($path, $data, $separator = PHP_EOL): bool
	{
		return $this->fileExists($path)
			? $this->put($path, $data . $separator . $this->get($path))
			: $this->put($path, $data);
	}

	/**
	 * Determine if temporary URLs can be generated.
	 */
	public function providesTemporaryUrls(): bool
	{
		return method_exists($this->adapter, 'getTemporaryUrl') ||
			isset($this->temporaryUrlCallback);
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  \Psr\Http\Message\StreamInterface|
	 *         \MVPS\Lumis\Framework\Http\File|
	 *         \MVPS\Lumis\Framework\Http\UploadedFile|
	 *         string|
	 *         resource  $contents
	 * @param  mixed  $options
	 * @return string|bool
	 */
	public function put($path, $contents, $options = []): string|bool
	{
		$options = is_string($options)
			? ['visibility' => $options]
			: (array) $options;

		if ($contents instanceof File || $contents instanceof UploadedFile) {
			return $this->putFile($path, $contents, $options);
		}

		try {
			if ($contents instanceof StreamInterface) {
				$this->driver->writeStream($path, $contents->detach(), $options);

				return true;
			}

			is_resource($contents)
				? $this->driver->writeStream($path, $contents, $options)
				: $this->driver->write($path, $contents, $options);
		} catch (RuntimeException $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * Store the uploaded file on the disk.
	 *
	 * @param  \MVPS\Lumis\Framework\Http\File|\MVPS\Lumis\Framework\Http\UploadedFile|string  $path
	 * @param  \MVPS\Lumis\Framework\Http\File|\MVPS\Lumis\Framework\Http\UploadedFile|string|array|null  $file
	 * @param  mixed  $options
	 * @return string|false
	 */
	public function putFile($path, $file = null, mixed $options = []): string|false
	{
		if (is_null($file) || is_array($file)) {
			[$path, $file, $options] = ['', $path, $file ?? []];
		}

		$file = is_string($file) ? new File($file) : $file;

		return $this->putFileAs($path, $file, $file->hashName(), $options);
	}

	/**
	 * Store the uploaded file on the disk with a given name.
	 *
	 * @param  \MVPS\Lumis\Framework\Http\File|\MVPS\Lumis\Framework\Http\UploadedFile|string  $path
	 * @param  \MVPS\Lumis\Framework\Http\File|\MVPS\Lumis\Framework\Http\UploadedFile|string|array|null  $file
	 * @param  string|array|null  $name
	 * @param  mixed  $options
	 * @return string|false
	 */
	public function putFileAs($path, $file, $name = null, mixed $options = []): string|false
	{
		if (is_null($name) || is_array($name)) {
			[$path, $file, $name, $options] = ['', $path, $file, $name ?? []];
		}

		$stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

		$path = trim($path . '/' . $name, '/');

		$result = $this->put($path, $stream, $options);

		if (is_resource($stream)) {
			fclose($stream);
		}

		return $result ? $path : false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToReadFile
	 */
	public function readStream($path)
	{
		try {
			return $this->driver->readStream($path);
		} catch (UnableToReadFile $e) {
			throw_if($this->throwsExceptions(), $e);

			return null;
		}
	}

	/**
	 * Replace the scheme, host and port of the given UriInterface with
	 * values from the given URL.
	 */
	protected function replaceBaseUrl(UriInterface $uri, string $url): UriInterface
	{
		$parsed = parse_url($url);

		return $uri
			->withScheme($parsed['scheme'])
			->withHost($parsed['host'])
			->withPort($parsed['port'] ?? null);
	}

	/**
	 * Create a streamed response for a given file.
	 */
	public function response(
		string $path,
		string|null $name = null,
		array $headers = [],
		string|null $disposition = 'inline'
	): StreamedResponse {
		$response = new StreamedResponse;

		$headers['Content-Type'] ??= $this->mimeType($path);
		$headers['Content-Length'] ??= $this->size($path);

		if (! array_key_exists('Content-Disposition', $headers)) {
			$filename = $name ?? basename($path);

			$disposition = $response->headerBag->makeDisposition(
				$disposition,
				$filename,
				$this->fallbackName($filename)
			);

			$headers['Content-Disposition'] = $disposition;
		}

		$response->headerBag->replace($headers);

		$response->setCallback(function () use ($path) {
			$stream = $this->readStream($path);

			fpassthru($stream);

			fclose($stream);
		});

		return $response;
	}

	/**
	 * Create a streamed download response for a given file.
	 */
	public function serve(
		Request $request,
		string $path,
		string|null $name = null,
		array $headers = []
	): StreamedResponse {
		return isset($this->serveCallback)
			? call_user_func($this->serveCallback, $request, $path, $headers)
			: $this->response($path, $name, $headers);
	}

	/**
	 * Define a custom callback that generates file download responses.
	 */
	public function serveUsing(Closure $callback): void
	{
		$this->serveCallback = $callback;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToSetVisibility
	 */
	public function setVisibility($path, $visibility): bool
	{
		try {
			$this->driver->setVisibility($path, $this->parseVisibility($visibility));
		} catch (UnableToSetVisibility $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function size($path): int
	{
		return $this->driver->fileSize($path);
	}

	/**
	 * Get a temporary upload URL for the file at the given path.
	 *
	 * @throws \RuntimeException
	 */
	public function temporaryUploadUrl(string $path, DateTimeInterface $expiration, array $options = []): array
	{
		if (method_exists($this->adapter, 'temporaryUploadUrl')) {
			return $this->adapter->temporaryUploadUrl($path, $expiration, $options);
		}

		throw new RuntimeException('This driver does not support creating temporary upload URLs.');
	}

	/**
	 * Get a temporary URL for the file at the given path.
	 *
	 * @throws \RuntimeException
	 */
	public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
	{
		if (method_exists($this->adapter, 'getTemporaryUrl')) {
			return $this->adapter->getTemporaryUrl($path, $expiration, $options);
		}

		if ($this->temporaryUrlCallback) {
			return $this->temporaryUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
		}

		throw new RuntimeException('This driver does not support creating temporary URLs.');
	}

	/**
	 * Determine if Flysystem exceptions should be thrown.
	 */
	protected function throwsExceptions(): bool
	{
		return (bool) ($this->config['throw'] ?? false);
	}

	/**
	 * Get the URL for the file at the given path.
	 *
	 * @throws \RuntimeException
	 */
	public function url(string $path): string
	{
		if (isset($this->config['prefix'])) {
			$path = $this->concatPathToUrl($this->config['prefix'], $path);
		}

		$adapter = $this->adapter;

		if (method_exists($adapter, 'getUrl')) {
			return $adapter->getUrl($path);
		} elseif (method_exists($this->driver, 'getUrl')) {
			return $this->driver->getUrl($path);
		} elseif ($adapter instanceof FtpAdapter || $adapter instanceof SftpAdapter) {
			return $this->getFtpUrl($path);
		} elseif ($adapter instanceof LocalAdapter) {
			return $this->getLocalUrl($path);
		}

		throw new RuntimeException('This driver does not support retrieving URLs.');
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \League\Flysystem\UnableToWriteFile
	 * @throws \League\Flysystem\UnableToSetVisibility
	 */
	public function writeStream($path, $resource, array $options = [])
	{
		try {
			$this->driver->writeStream($path, $resource, $options);
		} catch (RuntimeException $e) {
			throw_if($this->throwsExceptions(), $e);

			return false;
		}

		return true;
	}

	/**
	 * Pass dynamic methods call onto Flysystem.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		if (static::hasMacro($method)) {
			return $this->macroCall($method, $parameters);
		}

		return $this->driver->{$method}(...$parameters);
	}
}
