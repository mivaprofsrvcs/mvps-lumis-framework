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

namespace MVPS\Lumis\Framework\Console\Commands;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelPruningFinished;
use Illuminate\Database\Events\ModelPruningStarting;
use Illuminate\Database\Events\ModelsPruned;
use InvalidArgumentException;
use MVPS\Lumis\Framework\Collections\Collection;
use MVPS\Lumis\Framework\Console\Command;
use MVPS\Lumis\Framework\Contracts\Events\Dispatcher;
use MVPS\Lumis\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'model:prune')]
class ModelPruneCommand extends Command
{
	/**
	 * {@inheritdoc}
	 */
	protected $description = 'Prune models that are no longer needed';

	/**
	 * {@inheritdoc}
	 */
	protected $signature = 'model:prune
		{--model=* : Class names of the models to be pruned}
		{--except=* : Class names of the models to be excluded from pruning}
		{--path=* : Absolute path(s) to directories where models are located}
		{--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}
		{--pretend : Display the number of prunable records found instead of deleting them}';

	/**
	 * Get the path where models are located.
	 */
	protected function getPath(): array|string
	{
		$path = $this->option('path');

		if (! empty($path)) {
			return collection($path)
				->map(fn ($path) => base_path($path))
				->all();
		}

		return app_path('Models');
	}

	/**
	 * Execute the console command.
	 */
	public function handle(Dispatcher $events): void
	{
		$models = $this->models();

		if ($models->isEmpty()) {
			$this->components->info('No prunable models found.');

			return;
		}

		if ($this->option('pretend')) {
			$models->each(fn ($model) => $this->pretendToPrune($model));

			return;
		}

		$pruning = [];

		$events->listen(ModelsPruned::class, function ($event) use (&$pruning) {
			if (! in_array($event->model, $pruning)) {
				$pruning[] = $event->model;

				$this->newLine();

				$this->components->info(sprintf('Pruning [%s] records.', $event->model));
			}

			$this->components->twoColumnDetail($event->model, "{$event->count} records");
		});

		$events->dispatch(new ModelPruningStarting($models->all()));

		$models->each(function ($model) {
			$this->pruneModel($model);
		});

		$events->dispatch(new ModelPruningFinished($models->all()));

		$events->forget(ModelsPruned::class);
	}

	/**
	 * Determine if the given model class is prunable.
	 */
	protected function isPrunable(string $model): bool
	{
		$uses = class_uses_recursive($model);

		return in_array(Prunable::class, $uses) || in_array(MassPrunable::class, $uses);
	}

	/**
	 * Determine the models that should be pruned.
	 */
	protected function models(): Collection
	{
		$models = $this->option('model');

		if (! empty($models)) {
			return collection($models)
				->filter(fn ($model) => class_exists($model))
				->values();
		}

		$except = $this->option('except');

		if (! empty($models) && ! empty($except)) {
			throw new InvalidArgumentException('The --models and --except options cannot be combined.');
		}

		return collection(Finder::create()->in($this->getPath())->files()->name('*.php'))
			->map(function ($model) {
				$namespace = $this->lumis->getNamespace();

				return $namespace . str_replace(
					['/', '.php'],
					['\\', ''],
					Str::after($model->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
				);
			})
			->when(! empty($except), function ($models) use ($except) {
				return $models->reject(function ($model) use ($except) {
					return in_array($model, $except);
				});
			})
			->filter(fn ($model) => class_exists($model))
			->filter(fn ($model) => $this->isPrunable($model))
			->values();
	}

	/**
	 * Display how many models will be pruned.
	 */
	protected function pretendToPrune(string $model): void
	{
		$instance = new $model;

		$count = $instance->prunable()
			->when(
				in_array(SoftDeletes::class, class_uses_recursive(get_class($instance))),
				fn ($query) => $query->withTrashed()
			)
			->count();

		if ($count === 0) {
			$this->components->info("No prunable [$model] records found.");
		} else {
			$this->components->info("{$count} [{$model}] records will be pruned.");
		}
	}

	/**
	 * Prune the given model.
	 */
	protected function pruneModel(string $model): void
	{
		$instance = new $model;

		$chunkSize = property_exists($instance, 'prunableChunkSize')
			? $instance->prunableChunkSize
			: $this->option('chunk');

		$total = $this->isPrunable($model)
			? $instance->pruneAll($chunkSize)
			: 0;

		if ($total == 0) {
			$this->components->info("No prunable [$model] records found.");
		}
	}
}
