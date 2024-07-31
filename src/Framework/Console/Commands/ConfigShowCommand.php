<?php

namespace MVPS\Lumis\Framework\Console\Commands;

use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:show')]
class ConfigShowCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Display all of the values for a given configuration file or key';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'config:show {config : The configuration file or key to show}';

	/**
	 * Format the given configuration key.
	 */
	protected function formatKey(string $key): string
	{
		return preg_replace_callback(
			'/(.*)\.(.*)$/',
			fn ($matches) => sprintf(
				'<fg=gray>%s ⇁</> %s',
				str_replace('.', ' ⇁ ', $matches[1]),
				$matches[2]
			),
			$key
		);
	}

	/**
	 * Format the given configuration value.
	 */
	protected function formatValue(mixed $value): string
	{
		return match (true) {
			is_bool($value) => sprintf('<fg=#ef8414;options=bold>%s</>', $value ? 'true' : 'false'),
			is_null($value) => '<fg=#ef8414;options=bold>null</>',
			is_numeric($value) => "<fg=#ef8414;options=bold>{$value}</>",
			is_array($value) => '[]',
			is_object($value) => get_class($value),
			is_string($value) => $value,
			default => print_r($value, true),
		};
	}

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		$config = $this->argument('config');

		if (! config()->has($config)) {
			$this->components->error(
				"Configuration file or key <comment>{$config}</comment> does not exist."
			);

			return Command::FAILURE;
		}

		$this->newLine();
		$this->render($config);
		$this->newLine();

		return Command::SUCCESS;
	}

	/**
	 * Render the configuration values.
	 */
	public function render(string $name): void
	{
		$data = config($name);

		if (! is_array($data)) {
			$this->title($name, $this->formatValue($data));

			return;
		}

		$this->title($name);

		foreach (Arr::dot($data) as $key => $value) {
			$this->components->twoColumnDetail(
				$this->formatKey($key),
				$this->formatValue($value)
			);
		}
	}

	/**
	 * Render the title.
	 */
	public function title(string $title, string|null $subtitle = null): void
	{
		$this->components->twoColumnDetail(
			"<fg=green;options=bold>{$title}</>",
			$subtitle,
		);
	}
}
