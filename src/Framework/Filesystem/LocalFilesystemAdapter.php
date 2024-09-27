<?php

namespace MVPS\Lumis\Framework\Filesystem;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use RuntimeException;

class LocalFilesystemAdapter extends FilesystemAdapter
{
	use Conditionable;

	/**
	 * The name of the filesystem disk.
	 *
	 * @var string
	 */
	protected string $disk = '';

	/**
	 * Indicates if signed URLs should serve corresponding files.
	 *
	 * @var bool
	 */
	protected bool $shouldServeSignedUrls = false;

	/**
	 * The Closure that should be used to resolve the URL generator.
	 *
	 * @var \Closure|null
	 */
	protected Closure|null $urlGeneratorResolver = null;

	/**
	 * Specify the name of the disk the adapter is managing.
	 *
	 * @param  string  $disk
	 * @return $this
	 */
	public function diskName(string $disk)
	{
		$this->disk = $disk;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function providesTemporaryUrls(): bool
	{
		return $this->temporaryUrlCallback || (
			$this->shouldServeSignedUrls && $this->urlGeneratorResolver instanceof Closure
		);
	}

	/**
	 * Indicate that signed URLs should serve the corresponding files.
	 */
	public function shouldServeSignedUrls(bool $serve = true, Closure|null $urlGeneratorResolver = null): static
	{
		$this->shouldServeSignedUrls = $serve;
		$this->urlGeneratorResolver = $urlGeneratorResolver;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
	{
		if ($this->temporaryUrlCallback) {
			return $this->temporaryUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
		}

		if (! $this->providesTemporaryUrls()) {
			throw new RuntimeException('This driver does not support creating temporary URLs.');
		}

		$url = call_user_func($this->urlGeneratorResolver);

		return $url->to(
			$url->temporarySignedRoute(
				'storage.' . $this->disk,
				$expiration,
				['path' => $path],
				absolute: false
			)
		);
	}
}
