<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace MVPS\Lumis\Framework\Console;

use Closure;
use Illuminate\Console\ContainerCommandLoader;
use Illuminate\Support\ProcessUtils;
use MVPS\Lumis\Framework\Console\Events\LumisStarting;
use MVPS\Lumis\Framework\Contracts\Console\Application as ApplicationContract;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Contracts\Framework\Application as FrameworkApplication;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

class Application extends SymfonyApplication implements ApplicationContract
{
	/**
	 * The console application bootstrappers.
	 *
	 * @var array
	 */
	protected static array $bootstrappers = [];

	/**
	 * A map of command names to classes.
	 *
	 * @var array
	 */
	protected array $commandMap = [];

	/**
	 * The event dispatcher instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Events\Dispatcher
	 */
	protected Dispatcher $events;

	/**
	 * The output from the previous command.
	 *
	 * @var \Symfony\Component\Console\Output\BufferedOutput|null
	 */
	protected BufferedOutput|null $lastOutput = null;

	/**
	 * The Lumis application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Contracts\Framework\Application
	 */
	protected FrameworkApplication $lumis;

	/**
	 * Create a new console application instance.
	 */
	public function __construct(FrameworkApplication $lumis, Dispatcher $events, string $version)
	{
		parent::__construct('Lumis Framework', $version);

		$this->lumis = $lumis;
		$this->events = $events;

		$this->setAutoExit(false);
		$this->setCatchExceptions(false);

		$this->events->dispatch(new LumisStarting($this));

		$this->bootstrap();
	}

	/**
	 * {@inheritdoc}
	 */
	#[\Override]
	public function add(SymfonyCommand $command): SymfonyCommand|null
	{
		if ($command instanceof Command) {
			$command->setLumis($this->lumis);
		}

		return $this->addToParent($command);
	}

	/**
	 * Add the command to the parent instance.
	 */
	protected function addToParent(SymfonyCommand $command): SymfonyCommand
	{
		return parent::add($command);
	}

	/**
	 * Determine the proper Artisan executable.
	 *
	 * Used for compatibility with Illuminate console functionality.
	 */
	public static function artisanBinary(): string
	{
		return static::lumisBinary();
	}

	/**
	 * Bootstrap the console application.
	 */
	protected function bootstrap(): void
	{
		foreach (static::$bootstrappers as $bootstrapper) {
			$bootstrapper($this);
		}
	}

	/**
	 * Run a Lumis console command by name.
	 *
	 * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
	 */
	public function call(string $command, array $parameters = [], OutputInterface|null $outputBuffer = null): int
	{
		[$command, $input] = $this->parseCommand($command, $parameters);

		if (! $this->has($command)) {
			throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
		}

		$this->lastOutput = $outputBuffer ?: new BufferedOutput;

		return $this->run($input, $this->lastOutput);
	}

	/**
	 * Clear the console application bootstrappers.
	 */
	public static function forgetBootstrappers(): void
	{
		static::$bootstrappers = [];
	}

	/**
	 * Format the given command as a fully-qualified executable command.
	 */
	public static function formatCommandString(string $command): string
	{
		return sprintf('%s %s %s', static::phpBinary(), static::lumisBinary(), $command);
	}

	/**
	 * Get the default input definition for the application.
	 *
	 * This is used to add the --env option to every available command.
	 */
	#[\Override]
	protected function getDefaultInputDefinition(): InputDefinition
	{
		return tap(
			parent::getDefaultInputDefinition(),
			fn ($definition) => $definition->addOption($this->getEnvironmentOption())
		);
	}

	/**
	 * Get the global environment option for the definition.
	 */
	protected function getEnvironmentOption(): InputOption
	{
		$message = 'The environment the command should run under';

		return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
	}

	/**
	 * Get the Lumis application instance.
	 */
	public function getLumis(): FrameworkApplication
	{
		return $this->lumis;
	}

	/**
	 * Determine the proper Lumis executable.
	 */
	public static function lumisBinary(): string
	{
		return ProcessUtils::escapeArgument(defined('LUMIS_BINARY') ? LUMIS_BINARY : 'lumis');
	}

	/**
	 * Determine the proper PHP executable.
	 */
	public static function phpBinary(): string
	{
		return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
	}

	/**
	 * Get the output for the last run command.
	 */
	public function output(): string
	{
		return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
			? $this->lastOutput->fetch()
			: '';
	}

	/**
	 * Parse the incoming Lumis command and its input.
	 */
	protected function parseCommand(string $command, array $parameters): array
	{
		$callingClass = false;

		if (is_subclass_of($command, SymfonyCommand::class)) {
			$callingClass = true;

			$command = $this->lumis->make($command)
				->getName();
		}

		if ($callingClass === false && empty($parameters)) {
			$input = new StringInput($command);

			$command = $this->getCommandName($input);
		} else {
			array_unshift($parameters, $command);

			$input = new ArrayInput($parameters);
		}

		return [$command, $input];
	}

	/**
	 * Add a command, resolving through the application.
	 */
	public function resolve(Command|string $command): SymfonyCommand|null
	{
		if (is_subclass_of($command, SymfonyCommand::class) && ($commandName = $command::getDefaultName())) {
			foreach (explode('|', $commandName) as $name) {
				$this->commandMap[$name] = $command;
			}

			return null;
		}

		if ($command instanceof Command) {
			return $this->add($command);
		}

		return $this->add($this->lumis->make($command));
	}

	/**
	 * Resolve a list of commands through the application.
	 */
	public function resolveCommands(mixed $commands): static
	{
		$commands = is_array($commands) ? $commands : func_get_args();

		foreach ($commands as $command) {
			$this->resolve($command);
		}

		return $this;
	}

	/**
	 * Set the container command loader for lazy resolution.
	 */
	public function setContainerCommandLoader(): static
	{
		$this->setCommandLoader(new ContainerCommandLoader($this->lumis, $this->commandMap));

		return $this;
	}

	/**
	 * Register a console "starting" bootstrapper.
	 */
	public static function starting(Closure $callback): void
	{
		static::$bootstrappers[] = $callback;
	}
}
