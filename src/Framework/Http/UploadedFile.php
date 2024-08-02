<?php

namespace MVPS\Lumis\Framework\Http;

use Illuminate\Support\Traits\Macroable;
use MVPS\Lumis\Framework\Container\Container;
use MVPS\Lumis\Framework\Contracts\Filesystem\Factory as FilesystemFactory;
use MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException;
use MVPS\Lumis\Framework\Http\Traits\FileHelpers;
use MVPS\Lumis\Framework\Support\Arr;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFile extends SymfonyUploadedFile
{
	use FileHelpers;
	use Macroable;

	/**
	 * Get the file's extension supplied by the client.
	 */
	public function clientExtension(): string
	{
		return $this->guessClientExtension();
	}

	/**
	 * Get the contents of the uploaded file.
	 *
	 * @throws \MVPS\Lumis\Framework\Filesystem\Exceptions\FileNotFoundException
	 */
	public function get(): false|string
	{
		if (! $this->isValid()) {
			throw new FileNotFoundException("File does not exist at path {$this->getPathname()}.");
		}

		return file_get_contents($this->getPathname());
	}

	/**
	 * Create a new file instance from a base instance.
	 */
	public static function createFromBase(SymfonyUploadedFile $file, bool $test = false): static
	{
		return $file instanceof static ? $file : new static(
			$file->getPathname(),
			$file->getClientOriginalName(),
			$file->getClientMimeType(),
			$file->getError(),
			$test
		);
	}

	/**
	 * Parse and format the given options.
	 */
	protected function parseOptions(array|string $options): array
	{
		if (is_string($options)) {
			$options = ['disk' => $options];
		}

		return $options;
	}

	/**
	 * Store the uploaded file on a filesystem disk.
	 */
	public function store(string $path = '', array|string $options = []): string|false
	{
		return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
	}

	/**
	 * Store the uploaded file on a filesystem disk.
	 */
	public function storeAs(string $path, string|array $name = null, array|string $options = []): string|false
	{
		if (is_null($name) || is_array($name)) {
			[$path, $name, $options] = ['', $path, $name ?? []];
		}

		$options = $this->parseOptions($options);

		$disk = Arr::pull($options, 'disk');

		return Container::getInstance()->make(FilesystemFactory::class)
			->disk($disk)
			->putFileAs($path, $this, $name, $options);
	}

	/**
	 * Store the uploaded file on a filesystem disk with public visibility.
	 */
	public function storePublicly(string $path = '', array|string $options = []): string|false
	{
		$options = $this->parseOptions($options);

		$options['visibility'] = 'public';

		return $this->storeAs($path, $this->hashName(), $options);
	}

	/**
	 * Store the uploaded file on a filesystem disk with public visibility.
	 */
	public function storePubliclyAs(string $path, string $name = null, array|string $options = []): string|false
	{
		if (is_null($name) || is_array($name)) {
			[$path, $name, $options] = ['', $path, $name ?? []];
		}

		$options = $this->parseOptions($options);

		$options['visibility'] = 'public';

		return $this->storeAs($path, $name, $options);
	}
}
