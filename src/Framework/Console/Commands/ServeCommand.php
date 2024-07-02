<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use Carbon\Carbon;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Support\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Termwind\terminal;

#[AsCommand(name: 'serve')]
class ServeCommand extends Command
{
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $description = 'Serve the application on the PHP development server';

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $name = 'serve';

	/**
	 * The list of lines that are pending to be output.
	 *
	 * @var string
	 */
	protected string $outputBuffer = '';

	/**
	 * The current port offset.
	 *
	 * @var int
	 */
	protected int $portOffset = 0;

	/**
	 * The list of requests being handled and their start time.
	 *
	 * @var array<int, \Illuminate\Support\Carbon>
	 */
	protected array $requestsPool;

	/**
	 * Indicates if the "Server running on..." output message has been displayed.
	 *
	 * @var bool
	 */
	protected bool $serverRunningHasBeenDisplayed = false;

	/**
	 * The environment variables that should be passed from host machine to the PHP server process.
	 *
	 * @var string[]
	 */
	public static array $passthroughVariables = [
		'APP_ENV',
		'PATH',
		'PHP_CLI_SERVER_WORKERS',
		'PHP_IDE_CONFIG',
		'SYSTEMROOT',
		'XDEBUG_CONFIG',
		'XDEBUG_MODE',
		'XDEBUG_SESSION',
	];

	/**
	 * Check if the command has reached its maximum number of port tries.
	 */
	protected function canTryAnotherPort(): bool
	{
		return is_null($this->input->getOption('port')) && $this->input->getOption('tries') > $this->portOffset;
	}

	/**
	 * Execute the console command.
	 *
	 * @throws \Exception
	 */
	public function handle(): int
	{
		$environmentFile = $this->option('env')
			? base_path('.env') . '.' . $this->option('env')
			: base_path('.env');

		$hasEnvironment = file_exists($environmentFile);

		$environmentLastModified = $hasEnvironment
			? filemtime($environmentFile)
			: now()->addDays(30)->getTimestamp();

		$process = $this->startProcess($hasEnvironment);

		while ($process->isRunning()) {
			if ($hasEnvironment) {
				clearstatcache(false, $environmentFile);
			}

			if (
				! $this->option('no-reload') &&
				$hasEnvironment &&
				filemtime($environmentFile) > $environmentLastModified
			) {
				$environmentLastModified = filemtime($environmentFile);

				$this->newLine();

				$this->components->info('Environment modified. Restarting server...');

				$process->stop(5);

				$this->serverRunningHasBeenDisplayed = false;

				$process = $this->startProcess($hasEnvironment);
			}

			usleep(500 * 1000);
		}

		$status = $process->getExitCode();

		if ($status && $this->canTryAnotherPort()) {
			$this->portOffset += 1;

			return $this->handle();
		}

		return $status;
	}

	/**
	 * Returns a "callable" to handle the process output.
	 */
	protected function handleProcessOutput(): callable
	{
		return function ($type, $buffer) {
			$this->outputBuffer .= $buffer;

			$this->flushOutputBuffer();
		};
	}

	/**
	 * Flush the output buffer.
	 */
	protected function flushOutputBuffer(): void
	{
		$lines = str($this->outputBuffer)->explode("\n");

		$this->outputBuffer = (string) $lines->pop();

		$lines->map(fn ($line) => trim($line))
			->filter()
			->each(function ($line) {
				$strLine = str($line);

				if ($strLine->contains('Development Server (http')) {
					if ($this->serverRunningHasBeenDisplayed === false) {
						$this->serverRunningHasBeenDisplayed = true;

						$this->components->info(sprintf(
							'Server running on [http://%s:%s].',
							$this->host(),
							$this->port()
						));
						$this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');

						$this->newLine();
					}

					return;
				}

				if ($strLine->contains(' Accepted')) {
					$requestPort = $this->getRequestPortFromLine($line);

					$this->requestsPool[$requestPort] = [
						$this->getDateFromLine($line),
						false,
					];
				} elseif ($strLine->contains([' [200]: GET '])) {
					$requestPort = $this->getRequestPortFromLine($line);

					$this->requestsPool[$requestPort][1] = trim(explode('[200]: GET', $line)[1]);
				} elseif ($strLine->contains(' Closing')) {
					$requestPort = $this->getRequestPortFromLine($line);

					if (empty($this->requestsPool[$requestPort])) {
						$this->requestsPool[$requestPort] = [
							$this->getDateFromLine($line),
							false,
						];
					}

					[$startDate, $file] = $this->requestsPool[$requestPort];

					$formattedStartedAt = $startDate->format('Y-m-d H:i:s');

					unset($this->requestsPool[$requestPort]);

					[$date, $time] = explode(' ', $formattedStartedAt);

					$this->output->write("  <fg=gray>$date</> $time");

					$runTime = $this->getDateFromLine($line)->diffInSeconds($startDate);

					if ($file) {
						$this->output->write($file = " $file");
					}

					$terminalWidth = terminal()->width();

					$dots = max(
						$terminalWidth - mb_strlen($formattedStartedAt) - mb_strlen($file) - mb_strlen($runTime) - 9,
						0
					);

					$this->output->write(' ' . str_repeat('<fg=gray>.</>', $dots));
					$this->output->writeln(" <fg=gray>~ {$runTime}s</>");
				} elseif ($strLine->contains(['Closed without sending a request', 'Failed to poll event'])) {
					// ...
				} elseif (! empty($line)) {
					if ($strLine->startsWith('[')) {
						$line = $strLine->after('] ');
					}

					$this->output->writeln("  <fg=gray>$line</>");
				}
			});
	}

	/**
	 * Get the date from the given PHP server output.
	 */
	protected function getDateFromLine(string $line): Carbon
	{
		$regex = env('PHP_CLI_SERVER_WORKERS', 1) > 1
			? '/^\[\d+]\s\[([a-zA-Z0-9: ]+)\]/'
			: '/^\[([^\]]+)\]/';

		$line = str_replace('  ', ' ', $line);

		preg_match($regex, $line, $matches);

		return Carbon::createFromFormat('D M d H:i:s Y', $matches[1]);
	}

	/**
	 * Get the host and port from the host option string.
	 */
	protected function getHostAndPort(): array
	{
		if (preg_match('/(\[.*\]):?([0-9]+)?/', $this->input->getOption('host'), $matches) !== false) {
			return [
				$matches[1] ?? $this->input->getOption('host'),
				$matches[2] ?? null,
			];
		}

		$hostParts = explode(':', $this->input->getOption('host'));

		return [
			$hostParts[0],
			$hostParts[1] ?? null,
		];
	}

	/**
	 * Get the console command options.
	 */
	protected function getOptions(): array
	{
		return [
			[
				'host',
				null,
				InputOption::VALUE_OPTIONAL,
				'The host address to serve the application on',
				Env::get('SERVER_HOST', '127.0.0.1'),
			],
			[
				'port',
				null,
				InputOption::VALUE_OPTIONAL,
				'The port to serve the application on',
				Env::get('SERVER_PORT'),
			],
			[
				'tries',
				null,
				InputOption::VALUE_OPTIONAL,
				'The max number of ports to attempt to serve from',
				10,
			],
			[
				'no-reload',
				null,
				InputOption::VALUE_NONE,
				'Do not reload the development server on .env file changes',
			],
		];
	}

	/**
	 * Get the request port from the given PHP server output.
	 */
	protected function getRequestPortFromLine(string $line): int
	{
		preg_match('/:(\d+)\s(?:(?:\w+$)|(?:\[.*))/', $line, $matches);

		return (int) $matches[1];
	}

	/**
	 * Get the host for the command.
	 */
	protected function host(): string
	{
		[$host] = $this->getHostAndPort();

		return $host;
	}

	/**
	 * Get the port for the command.
	 */
	protected function port(): string
	{
		$port = $this->input->getOption('port');

		if (is_null($port)) {
			[, $port] = $this->getHostAndPort();
		}

		$port = $port ?: 8000;

		return $port + $this->portOffset;
	}

	/**
	 * Get the full server command.
	 */
	protected function serverCommand(): array
	{
		$server = file_exists(base_path('server.php'))
			? base_path('server.php')
			: Application::FRAMEWORK_RESOURCES_PATH . '/server.php';

		return [
			(new PhpExecutableFinder)->find(false),
			'-S',
			$this->host() . ':' . $this->port(),
			$server,
		];
	}

	/**
	 * Start a new server process.
	 */
	protected function startProcess(bool $hasEnvironment): Process
	{
		$process = new Process(
			$this->serverCommand(),
			public_path(),
			collection($_ENV)->mapWithKeys(function ($value, $key) use ($hasEnvironment) {
				if ($this->option('no-reload') || ! $hasEnvironment) {
					return [$key => $value];
				}

				return in_array($key, static::$passthroughVariables) ? [$key => $value] : [$key => false];
			})->all()
		);

		$this->trap(fn () => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
			if ($process->isRunning()) {
				$process->stop(10, $signal);
			}

			exit;
		});

		$process->start($this->handleProcessOutput());

		return $process;
	}
}
