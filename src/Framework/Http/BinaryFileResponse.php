<?php

namespace MVPS\Lumis\Framework\Http;

use DateTimeImmutable;
use LogicException;
use MVPS\Lumis\Framework\Http\Request;
use SplFileInfo;
use SplFileObject;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\HeaderUtils;

class BinaryFileResponse extends Response
{
	/**
	 * The size of each chunk to read from the file.
	 *
	 * @var int
	 */
	protected int $chunkSize = 16 * 1024;

	/**
	 * Indicates whether to delete the file after sending the content.
	 *
	 * @var bool
	 */
	protected bool $deleteFileAfterSend = false;

	/**
	 * The file instance representing the response content.
	 *
	 * @var \Symfony\Component\HttpFoundation\File\File
	 */
	protected File $file;

	/**
	 * The maximum number of bytes to read from the file.
	 *
	 * A negative value indicates no limit.
	 *
	 * @var int
	 */
	protected int $maxlen = -1;

	/**
	 * The starting offset for reading the file.
	 *
	 * @var int
	 */
	protected int $offset = 0;

	/**
	 * Temporary file object used for handling large file uploads.
	 *
	 * @var \SplTempFileObject|null
	 */
	protected SplTempFileObject|null $tempFileObject = null;

	/**
	 * Indicates whether to trust the X-Sendfile header.
	 *
	 * @var bool
	 */
	protected static bool $trustXSendfileTypeHeader = false;

	/**
	 * Create a new binary file HTTP response instance.
	 *
	 * Constructs a response representing a file resource, setting default
	 * headers and options.
	 */
	public function __construct(
		SplFileInfo|string $file,
		int $status = 200,
		array $headers = [],
		bool $public = true,
		string|null $contentDisposition = null,
		bool $autoEtag = false,
		bool $autoLastModified = true
	) {
		parent::__construct(null, $status, $headers);

		$this->setFile($file, $contentDisposition, $autoEtag, $autoLastModified);

		if ($public) {
			$this->setPublic();
		}
	}

	/**
	 * Configures whether to delete the file after sending the response.
	 *
	 * If set to true, the file will be deleted once the response is sent.
	 * This behavior is overridden if the `X-Sendfile` header is used.
	 */
	public function deleteFileAfterSend(bool $shouldDelete = true): static
	{
		$this->deleteFileAfterSend = $shouldDelete;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getContent(): string|false
	{
		return false;
	}

	/**
	 * Retrieves the file instance associated with the response.
	 */
	public function getFile(): File
	{
		return $this->file;
	}

	/**
	 * Checks if the provided If-Range header is valid.
	 */
	protected function hasValidIfRangeHeader(string|null $header): bool
	{
		if ($this->getEtag() === $header) {
			return true;
		}

		$lastModified = $this->getLastModified();

		if (is_null($lastModified)) {
			return false;
		}

		return $lastModified->format('D, d M Y H:i:s') . ' GMT' === $header;
	}

	/**
	 * Prepares the file download response.
	 */
	public function prepare(Request $request): static
	{
		$response = $this;

		if ($response->isInformational() || $response->isEmpty()) {
			parent::prepare($request);

			$response->maxlen = 0;

			return $response;
		}

		if (! $response->headerBag->has('Content-Type')) {
			$response->headerBag->set(
				'Content-Type',
				$response->file->getMimeType() ?: 'application/octet-stream'
			);
		}

		parent::prepare($request);

		$response->offset = 0;
		$response->maxlen = -1;

		$fileSize = $response->file->getSize();

		if ($fileSize === false) {
			return $response;
		}

		$response->headerBag->remove('Transfer-Encoding');

		$response->headerBag->set('Content-Length', $fileSize);

		// Set the Accept-Ranges header based on the request method.
		if (! $response->headerBag->has('Accept-Ranges')) {
			// For safe HTTP methods, allow byte ranges; otherwise, disable them.
			$response->headerBag->set('Accept-Ranges', $request->isMethodSafe() ? 'bytes' : 'none');
		}

		if (static::$trustXSendfileTypeHeader && $request->headerBag->has('X-Sendfile-Type')) {
			// If the X-Sendfile header is present, use it to send the file directly.
			$type = $request->headerBag->get('X-Sendfile-Type');
			$path = $response->file->getRealPath();

			// Fallback to using the file path if real path is not available.
			if ($path === false) {
				$path = $response->file->getPathname();
			}

			if (strtolower($type) === 'x-accel-redirect') {
				// Process X-Accel-Mapping header substitutions based on the
				// X-Accel-Mapping header value, which is typically used by
				// Nginx to configure content acceleration.
				// @link https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/#x-accel-redirect
				$parts = HeaderUtils::split($request->headerBag->get('X-Accel-Mapping', ''), ',=');

				foreach ($parts as $part) {
					[$pathPrefix, $location] = $part;

					// Set X-Accel-Redirect header only if a valid URI can be
					// generated, as nginx cannot serve arbitrary file paths.
					if (str_starts_with($path, $pathPrefix)) {
						$path = $location . substr($path, strlen($pathPrefix));

						$response->headerBag->set($type, $path);
						$response->maxlen = 0;

						break;
					}
				}
			} else {
				$response->headerBag->set($type, $path);
				$response->maxlen = 0;
			}
		} elseif ($request->headerBag->has('Range') && $request->isMethod('GET')) {
			if (
				! $request->headerBag->has('If-Range') ||
				$response->hasValidIfRangeHeader($request->headerBag->get('If-Range'))
			) {
				$range = $request->headerBag->get('Range');

				if (str_starts_with($range, 'bytes=')) {
					[$start, $end] = explode('-', substr($range, 6), 2) + [1 => 0];

					$end = $end === '' ? $fileSize - 1 : (int) $end;

					if ($start === '') {
						$start = $fileSize - $end;
						$end = $fileSize - 1;
					} else {
						$start = (int) $start;
					}

					if ($start <= $end) {
						$end = min($end, $fileSize - 1);

						if ($start < 0 || $start > $end) {
							$response = $response->withStatus(416);

							$response->headerBag->set('Content-Range', sprintf('bytes */%s', $fileSize));
						} elseif ($end - $start < $fileSize - 1) {
							$response->maxlen = $end < $fileSize ? $end - $start + 1 : -1;
							$response->offset = $start;

							$response = $response->withStatus(206);

							$response->headerBag->set(
								'Content-Range',
								sprintf('bytes %s-%s/%s', $start, $end, $fileSize)
							);

							$response->headerBag->set('Content-Length', $end - $start + 1);
						}
					}
				}
			}
		}

		if ($request->isMethod('HEAD')) {
			$response->maxlen = 0;
		}

		return $response;
	}

	/**
	 * Sends the file content to the output buffer.
	 */
	public function sendContent(): static
	{
		try {
			if (! $this->isSuccessful() || $this->maxlen === 0) {
				return $this;
			}

			$output = fopen('php://output', 'w');

			if ($this->tempFileObject) {
				$file = $this->tempFileObject;

				$file->rewind();
			} else {
				$file = new SplFileObject($this->file->getPathname(), 'r');
			}

			ignore_user_abort(true);

			if ($this->offset !== 0) {
				$file->fseek($this->offset);
			}

			$length = $this->maxlen;

			while ($length && ! $file->eof()) {
				$read = $length > $this->chunkSize || 0 > $length ? $this->chunkSize : $length;

				$data = $file->fread($read);

				if ($data === false) {
					break;
				}

				while ($data !== '') {
					$read = fwrite($output, $data);

					if ($read === false || connection_aborted()) {
						break 2;
					}

					if ($length > 0) {
						$length -= $read;
					}

					$data = substr($data, $read);
				}
			}

			fclose($output);
		} finally {
			if (is_null($this->tempFileObject) && $this->deleteFileAfterSend && is_file($this->file->getPathname())) {
				unlink($this->file->getPathname());
			}
		}

		return $this;
	}

	/**
	 * Automatically generates and sets the ETag header based on the
	 * file's content.
	 */
	public function setAutoEtag(): static
	{
		$this->setEtag(
			base64_encode(hash_file('xxh128', $this->file->getPathname(), true))
		);

		return $this;
	}

	/**
	 * Sets the Last-Modified header based on the file's modification time.
	 */
	public function setAutoLastModified(): static
	{
		$this->setLastModified(
			DateTimeImmutable::createFromFormat('U', $this->file->getMTime())
		);

		return $this;
	}

	/**
	 * Sets the chunk size for streaming the file content.
	 */
	public function setChunkSize(int $chunkSize): static
	{
		if ($chunkSize < 1 || $chunkSize > PHP_INT_MAX) {
			throw new LogicException(
				'Invalid chunk size: must be a positive integer less than or equal to PHP_INT_MAX.'
			);
		}

		$this->chunkSize = $chunkSize;

		return $this;
	}

	/**
	 * Prevents setting content on a binary file response.
	 *
	 * @throws \LogicException
	 */
	public function setContent(mixed $content): static
	{
		if (! is_null($content)) {
			throw new LogicException(
				'Setting content on a BinaryFileResponse is not supported.' .
				' Use the file property to specify the file to be sent.'
			);
		}

		return $this;
	}

	/**
	 * Sets the Content-Disposition header for the response.
	 */
	public function setContentDisposition(
		string $disposition,
		string $filename = '',
		string $filenameFallback = ''
	): static {
		if ($filename === '') {
			$filename = $this->file->getFilename();
		}

		if (
			$filenameFallback === '' &&
			(! preg_match('/^[\x20-\x7e]*$/', $filename) || str_contains($filename, '%'))
		) {
			$encoding = mb_detect_encoding($filename, null, true) ?: '8bit';

			for ($i = 0, $filenameLength = mb_strlen($filename, $encoding); $i < $filenameLength; ++$i) {
				$char = mb_substr($filename, $i, 1, $encoding);

				if ($char === '%' || ord($char) < 32 || ord($char) > 126) {
					$filenameFallback .= '_';
				} else {
					$filenameFallback .= $char;
				}
			}
		}

		$dispositionHeader = $this->headerBag->makeDisposition($disposition, $filename, $filenameFallback);

		$this->headerBag->set('Content-Disposition', $dispositionHeader);

		return $this;
	}

	/**
	 * Sets the file to stream.
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
	 */
	public function setFile(
		SplFileInfo|string $file,
		string|null $contentDisposition = null,
		bool $autoEtag = false,
		bool $autoLastModified = true
	): static {
		$isTemporaryFile = $file instanceof SplTempFileObject;

		$this->tempFileObject = $isTemporaryFile ? $file : null;

		if (! $file instanceof File) {
			$file = $file instanceof SplFileInfo
				? new File($file->getPathname(), ! $isTemporaryFile)
				: new File((string) $file);
		}

		if (! $file->isReadable() && ! $isTemporaryFile) {
			throw new FileException('The specified file is not readable: ' . $file->getPathname());
		}

		$this->file = $file;

		if ($autoEtag) {
			$this->setAutoEtag();
		}

		if ($autoLastModified && ! $isTemporaryFile) {
			$this->setAutoLastModified();
		}

		if ($contentDisposition) {
			$this->setContentDisposition($contentDisposition);
		}

		return $this;
	}

	/**
	 * Enables trusting the X-Sendfile-Type header.
	 */
	public static function trustXSendfileTypeHeader(): void
	{
		self::$trustXSendfileTypeHeader = true;
	}
}
