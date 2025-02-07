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
use Illuminate\Support\Traits\ForwardsCalls;
use MVPS\Lumis\Framework\Console\Exceptions\ManuallyFailedException;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClosureCommand extends Command
{
	use ForwardsCalls;

	/**
	 * The command callback.
	 *
	 * @var \Closure
	 */
	protected Closure $callback;

	/**
	 * Create a new closure command instance.
	 */
	public function __construct(string $signature, Closure $callback)
	{
		$this->callback = $callback;
		$this->signature = $signature;

		parent::__construct();
	}

	/**
	 * Set the description for the command.
	 */
	public function describe(string $description): static
	{
		$this->setDescription($description);

		return $this;
	}

	/**
	 * Execute the console command.
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$inputs = array_merge($input->getArguments(), $input->getOptions());

		$parameters = [];

		foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
			if (isset($inputs[$parameter->getName()])) {
				$parameters[$parameter->getName()] = $inputs[$parameter->getName()];
			}
		}

		try {
			return (int) $this->lumis->call($this->callback->bindTo($this, $this), $parameters);
		} catch (ManuallyFailedException $e) {
			$this->components->error($e->getMessage());

			return static::FAILURE;
		}
	}

	/**
	 * Set the description for the command.
	 */
	public function purpose(string $description): static
	{
		return $this->describe($description);
	}

	/**
	 * Create a new scheduled event for the command.
	 *
	 * TODO: Update this when Implementing scheduling
	 */
	// public function schedule(array $parameters = []): Scheduling\Event
	public function schedule(array $parameters = []): void
	{
		// return Schedule::command($this->name, $parameters);
	}

	/**
	 * Dynamically proxy calls to a new scheduled event.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		return $this->forwardCallTo($this->schedule(), $method, $parameters);
	}
}
