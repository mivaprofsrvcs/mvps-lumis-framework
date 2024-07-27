<?php

namespace MVPS\Lumis\Framework\Console;

use Illuminate\Console\CacheCommandMutex;
use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Console\CommandMutex;
use Illuminate\Console\ManuallyFailedException;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Console\Isolatable;
use MVPS\Lumis\Framework\Contracts\Framework\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends IlluminateCommand
{
	/**
	 * Overwrite the Laravel application reference for Illuminate Command instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected $laravel;

	/**
	 * The Lumis application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected Application|null $lumis = null;

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function commandIsolationMutex()
	{
		return $this->lumis->bound(CommandMutex::class)
			? $this->lumis->make(CommandMutex::class)
			: $this->lumis->make(CacheCommandMutex::class);
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if (
			$this instanceof Isolatable && $this->option('isolated') !== false &&
			! $this->commandIsolationMutex()->create($this)
		) {
			$this->comment(sprintf('The [%s] command is already running.', $this->getName()));

			return (int) (is_numeric($this->option('isolated'))
				? $this->option('isolated')
				: $this->isolatedExitCode);
		}

		$method = method_exists($this, 'handle') ? 'handle' : '__invoke';

		try {
			return (int) $this->lumis->call([$this, $method]);
		} catch (ManuallyFailedException $e) {
			$this->components->error($e->getMessage());

			return static::FAILURE;
		} finally {
			if ($this instanceof Isolatable && $this->option('isolated') !== false) {
				$this->commandIsolationMutex()->forget($this);
			}
		}
	}

	/**
	 * Get the Lumis application instance.
	 */
	public function getLumis(): Application
	{
		return $this->lumis;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	protected function resolveCommand($command)
	{
		if (! class_exists($command)) {
			return $this->getApplication()->find($command);
		}

		$command = $this->lumis->make($command);

		if ($command instanceof SymfonyCommand) {
			$command->setApplication($this->getApplication());
		}

		if ($command instanceof self) {
			$command->setLumis($this->getLumis());
		}

		return $command;
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function run(InputInterface $input, OutputInterface $output): int
	{
		$this->output = $output instanceof OutputStyle
			? $output
			: $this->lumis->make(OutputStyle::class, ['input' => $input, 'output' => $output]);

		$this->components = $this->lumis->make(Factory::class, ['output' => $this->output]);

		$this->configurePrompts($input);

		try {
			return SymfonyCommand::run($this->input = $input, $this->output);
		} finally {
			$this->untrap();
		}
	}

	/**
	 * Set the Lumis and Laravel application instance.
	 */
	public function setLumis(Application $lumis): static
	{
		$this->lumis = $lumis;
		$this->laravel = $lumis;

		return $this;
	}
}
